import React, { useState } from 'react';
import { useHistory } from 'react-router-dom';
import tw from 'twin.macro';
import PageContentBlock from '@/components/elements/PageContentBlock';
import Button from '@/components/elements/Button';
import Input from '@/components/elements/Input';
import Label from '@/components/elements/Label';
import { useStoreState } from '@/state/hooks';

const CheckoutPage: React.FC = () => {
    const history = useHistory();
    const user = useStoreState((state) => state.user.data);
    const isAuthenticated = !!user;

    const [billingInfo, setBillingInfo] = useState({
        billing_name: user ? `${user.name_first || ''} ${user.name_last || ''}`.trim() : '',
        billing_email: user?.email || '',
        billing_address: '',
        billing_city: '',
        billing_state: '',
        billing_country: '',
        billing_postal_code: '',
    });

    const [accountInfo, setAccountInfo] = useState({
        create_account: !isAuthenticated,
        password: '',
        password_confirmation: '',
    });

    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setError('');
        setLoading(true);

        if (!isAuthenticated && accountInfo.create_account) {
            if (accountInfo.password !== accountInfo.password_confirmation) {
                setError('Passwords do not match');
                setLoading(false);
                return;
            }
            if (accountInfo.password.length < 8) {
                setError('Password must be at least 8 characters');
                setLoading(false);
                return;
            }
        }

        try {
            const response = await fetch('/checkout/complete', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN':
                        (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
                },
                body: JSON.stringify({
                    ...billingInfo,
                    ...(accountInfo.create_account && {
                        register: true,
                        password: accountInfo.password,
                    }),
                }),
            });

            const data = await response.json();

            if (response.ok) {
                alert('Order completed successfully! Your server is being created.');
                history.push('/');
            } else {
                setError(data.error || 'Failed to complete checkout');
            }
        } catch (err) {
            setError('An error occurred during checkout');
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    return (
        <PageContentBlock title='Checkout'>
            <div css={tw`max-w-4xl mx-auto`}>
                {error && <div css={tw`bg-red-500 text-white p-4 rounded mb-4`}>{error}</div>}

                <form onSubmit={handleSubmit}>
                    <div css={tw`grid grid-cols-1 lg:grid-cols-2 gap-6`}>
                        {/* Left Column - Billing Information */}
                        <div css={tw`bg-neutral-700 rounded-lg p-6`}>
                            <h2 css={tw`text-2xl font-bold mb-6`}>Billing Information</h2>

                            <div css={tw`space-y-4`}>
                                <div>
                                    <Label>Full Name</Label>
                                    <Input
                                        type='text'
                                        value={billingInfo.billing_name}
                                        onChange={(e) =>
                                            setBillingInfo({ ...billingInfo, billing_name: e.target.value })
                                        }
                                        required
                                    />
                                </div>

                                <div>
                                    <Label>Email Address</Label>
                                    <Input
                                        type='email'
                                        value={billingInfo.billing_email}
                                        onChange={(e) =>
                                            setBillingInfo({ ...billingInfo, billing_email: e.target.value })
                                        }
                                        required
                                    />
                                </div>

                                <div>
                                    <Label>Address</Label>
                                    <Input
                                        type='text'
                                        value={billingInfo.billing_address}
                                        onChange={(e) =>
                                            setBillingInfo({ ...billingInfo, billing_address: e.target.value })
                                        }
                                    />
                                </div>

                                <div css={tw`grid grid-cols-2 gap-4`}>
                                    <div>
                                        <Label>City</Label>
                                        <Input
                                            type='text'
                                            value={billingInfo.billing_city}
                                            onChange={(e) =>
                                                setBillingInfo({ ...billingInfo, billing_city: e.target.value })
                                            }
                                        />
                                    </div>
                                    <div>
                                        <Label>State/Province</Label>
                                        <Input
                                            type='text'
                                            value={billingInfo.billing_state}
                                            onChange={(e) =>
                                                setBillingInfo({ ...billingInfo, billing_state: e.target.value })
                                            }
                                        />
                                    </div>
                                </div>

                                <div css={tw`grid grid-cols-2 gap-4`}>
                                    <div>
                                        <Label>Country</Label>
                                        <Input
                                            type='text'
                                            value={billingInfo.billing_country}
                                            onChange={(e) =>
                                                setBillingInfo({ ...billingInfo, billing_country: e.target.value })
                                            }
                                        />
                                    </div>
                                    <div>
                                        <Label>Postal Code</Label>
                                        <Input
                                            type='text'
                                            value={billingInfo.billing_postal_code}
                                            onChange={(e) =>
                                                setBillingInfo({ ...billingInfo, billing_postal_code: e.target.value })
                                            }
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Right Column - Account & Payment */}
                        <div css={tw`space-y-6`}>
                            {/* Account Creation */}
                            {!isAuthenticated && (
                                <div css={tw`bg-neutral-700 rounded-lg p-6`}>
                                    <h2 css={tw`text-2xl font-bold mb-6`}>Create Account</h2>

                                    <div css={tw`space-y-4`}>
                                        <div>
                                            <Label>Password</Label>
                                            <Input
                                                type='password'
                                                value={accountInfo.password}
                                                onChange={(e) =>
                                                    setAccountInfo({ ...accountInfo, password: e.target.value })
                                                }
                                                required={accountInfo.create_account}
                                            />
                                            <p css={tw`text-xs text-neutral-400 mt-1`}>Minimum 8 characters</p>
                                        </div>

                                        <div>
                                            <Label>Confirm Password</Label>
                                            <Input
                                                type='password'
                                                value={accountInfo.password_confirmation}
                                                onChange={(e) =>
                                                    setAccountInfo({
                                                        ...accountInfo,
                                                        password_confirmation: e.target.value,
                                                    })
                                                }
                                                required={accountInfo.create_account}
                                            />
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Order Summary */}
                            <div css={tw`bg-neutral-700 rounded-lg p-6`}>
                                <h3 css={tw`text-xl font-bold mb-4`}>Order Summary</h3>
                                <p css={tw`text-neutral-400 mb-4`}>Review your cart before completing the order.</p>

                                <Button type='submit' disabled={loading} css={tw`w-full`}>
                                    {loading ? 'Processing...' : 'Complete Order'}
                                </Button>

                                <p css={tw`text-xs text-neutral-400 mt-4 text-center`}>
                                    By completing this order, you agree to our Terms of Service and Privacy Policy.
                                </p>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </PageContentBlock>
    );
};

export default CheckoutPage;
