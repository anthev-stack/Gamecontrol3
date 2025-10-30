<?php

namespace Pterodactyl\Http\Controllers\Base;

use Illuminate\View\View;
use Illuminate\View\Factory as ViewFactory;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;

class IndexController extends Controller
{
    /**
     * IndexController constructor.
     */
    public function __construct(
        protected ServerRepositoryInterface $repository,
        protected ViewFactory $view
    ) {
    }

    /**
     * Returns listing of user's servers OR marketplace homepage.
     */
    public function index(): View
    {
        // If user is authenticated, show their dashboard
        // If not authenticated, show marketplace homepage
        return $this->view->make('templates/base.core');
    }
    
    /**
     * Returns user's server dashboard (authenticated users only).
     */
    public function dashboard(): View
    {
        return $this->view->make('templates/base.core');
    }
}
