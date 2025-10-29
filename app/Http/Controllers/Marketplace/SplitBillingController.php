<?php

namespace Pterodactyl\Http\Controllers\Marketplace;

use Pterodactyl\Models\Server;
use Pterodactyl\Models\BillingInvitation;
use Pterodactyl\Models\ServerBillingShare;
use Pterodactyl\Models\User;
use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SplitBillingController extends Controller
{
    /**
     * Get billing shares for a server.
     */
    public function getServerShares(Server $server): JsonResponse
    {
        // Check if user owns or has access to this server
        if ($server->owner_id !== auth()->id() && !$server->subusers()->where('user_id', auth()->id())->exists()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $shares = ServerBillingShare::where('server_id', $server->id)
            ->with('user:id,username,email,name_first,name_last')
            ->get();

        return response()->json(['data' => $shares]);
    }

    /**
     * Send billing split invitation.
     */
    public function sendInvitation(Request $request, Server $server): JsonResponse
    {
        // Only server owner can send invitations
        if ($server->owner_id !== auth()->id()) {
            return response()->json(['error' => 'Only server owner can send invitations'], 403);
        }

        $request->validate([
            'email' => 'required|email',
            'message' => 'nullable|string|max:500',
        ]);

        // Check if already has active billing share
        $existingShare = ServerBillingShare::where('server_id', $server->id)
            ->whereHas('user', function ($q) use ($request) {
                $q->where('email', $request->email);
            })
            ->where('status', 'active')
            ->exists();

        if ($existingShare) {
            return response()->json(['error' => 'This user is already sharing billing for this server'], 400);
        }

        // Check if there's a pending invitation
        $pendingInvite = BillingInvitation::where('server_id', $server->id)
            ->where('invitee_email', $request->email)
            ->where('status', 'pending')
            ->first();

        if ($pendingInvite) {
            return response()->json(['error' => 'There is already a pending invitation for this email'], 400);
        }

        // Calculate share percentage (50/50 for now, can be customized)
        $currentShares = ServerBillingShare::where('server_id', $server->id)->count();
        $sharePercentage = 50.00; // 50/50 split

        try {
            DB::beginTransaction();

            // Create invitation
            $invitation = BillingInvitation::create([
                'server_id' => $server->id,
                'inviter_id' => auth()->id(),
                'invitee_email' => $request->email,
                'share_percentage' => $sharePercentage,
                'message' => $request->message,
                'status' => 'pending',
            ]);

            // TODO: Send email notification
            // Mail::to($request->email)->send(new BillingInvitationMail($invitation));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Invitation sent successfully',
                'data' => $invitation,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Failed to send invitation: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user's billing invitations.
     */
    public function getInvitations(): JsonResponse
    {
        $user = auth()->user();

        $invitations = BillingInvitation::where('invitee_email', $user->email)
            ->where('status', 'pending')
            ->with(['server', 'inviter:id,username,email,name_first,name_last'])
            ->get();

        return response()->json(['data' => $invitations]);
    }

    /**
     * Accept billing invitation.
     */
    public function acceptInvitation(Request $request, string $token): JsonResponse
    {
        $invitation = BillingInvitation::where('token', $token)->firstOrFail();

        if (!$invitation->isPending()) {
            return response()->json(['error' => 'This invitation is no longer valid'], 400);
        }

        $user = auth()->user();

        if ($invitation->invitee_email !== $user->email) {
            return response()->json(['error' => 'This invitation was sent to a different email address'], 403);
        }

        try {
            DB::beginTransaction();

            // Accept invitation
            $invitation->accept($user->id);

            // Create billing share
            ServerBillingShare::create([
                'server_id' => $invitation->server_id,
                'user_id' => $user->id,
                'share_percentage' => $invitation->share_percentage,
                'status' => 'active',
                'has_server_access' => true,
            ]);

            // Update owner's share to 50%
            ServerBillingShare::updateOrCreate(
                [
                    'server_id' => $invitation->server_id,
                    'user_id' => $invitation->inviter_id,
                ],
                [
                    'share_percentage' => 50.00,
                    'status' => 'active',
                    'has_server_access' => true,
                ]
            );

            // Add user as subuser to the server
            $server = Server::findOrFail($invitation->server_id);
            
            // Create subuser with full permissions
            $subuser = $server->subusers()->create([
                'user_id' => $user->id,
                'permissions' => ['*'], // Full access
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Successfully joined billing split',
                'data' => [
                    'server' => $server,
                    'share_percentage' => $invitation->share_percentage,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Failed to accept invitation: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Decline billing invitation.
     */
    public function declineInvitation(string $token): JsonResponse
    {
        $invitation = BillingInvitation::where('token', $token)->firstOrFail();

        if (!$invitation->isPending()) {
            return response()->json(['error' => 'This invitation is no longer valid'], 400);
        }

        $user = auth()->user();

        if ($invitation->invitee_email !== $user->email) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $invitation->decline();

        return response()->json([
            'success' => true,
            'message' => 'Invitation declined',
        ]);
    }

    /**
     * Cancel billing invitation (by inviter).
     */
    public function cancelInvitation(BillingInvitation $invitation): JsonResponse
    {
        if ($invitation->inviter_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($invitation->status !== 'pending') {
            return response()->json(['error' => 'Cannot cancel this invitation'], 400);
        }

        $invitation->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'Invitation cancelled',
        ]);
    }

    /**
     * Remove user from billing split.
     */
    public function removeShare(Server $server, User $user): JsonResponse
    {
        // Only server owner can remove shares
        if ($server->owner_id !== auth()->id()) {
            return response()->json(['error' => 'Only server owner can remove billing shares'], 403);
        }

        $share = ServerBillingShare::where('server_id', $server->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        try {
            DB::beginTransaction();

            // Remove billing share
            $share->delete();

            // Remove as subuser
            $server->subusers()->where('user_id', $user->id)->delete();

            // Reset owner's share to 100%
            $ownerShare = ServerBillingShare::where('server_id', $server->id)
                ->where('user_id', $server->owner_id)
                ->first();

            if ($ownerShare) {
                $ownerShare->update(['share_percentage' => 100.00]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User removed from billing split',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Failed to remove user: ' . $e->getMessage(),
            ], 500);
        }
    }
}

