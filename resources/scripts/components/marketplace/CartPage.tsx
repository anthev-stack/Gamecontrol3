import React, { useEffect, useState } from 'react';
import { useHistory } from 'react-router-dom';
import tw from 'twin.macro';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faTrash, faShoppingCart } from '@fortawesome/free-solid-svg-icons';
import PageContentBlock from '@/components/elements/PageContentBlock';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';

interface CartItem {
    id: number;
    plan: {
        id: number;
        name: string;
        slug: string;
        price: number;
        billing_period: string;
    };
    quantity: number;
    price_at_time: number;
    subtotal: number;
}

interface Cart {
    id: number;
    uuid: string;
    items: CartItem[];
    total: number;
    item_count: number;
}

const CartPage: React.FC = () => {
    const history = useHistory();
    const [cart, setCart] = useState<Cart | null>(null);
    const [loading, setLoading] = useState(true);

    const fetchCart = async () => {
        try {
            const response = await fetch('/cart/show');
            const data = await response.json();
            setCart(data.data);
            setLoading(false);
        } catch (error) {
            console.error('Error fetching cart:', error);
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchCart();
    }, []);

    const removeItem = async (itemId: number) => {
        try {
            const response = await fetch(`/cart/remove/${itemId}`, {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (response.ok) {
                fetchCart();
            }
        } catch (error) {
            console.error('Error removing item:', error);
        }
    };

    const updateQuantity = async (itemId: number, quantity: number) => {
        if (quantity < 1) return;

        try {
            const response = await fetch(`/cart/update/${itemId}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ quantity }),
            });

            if (response.ok) {
                fetchCart();
            }
        } catch (error) {
            console.error('Error updating quantity:', error);
        }
    };

    const clearCart = async () => {
        if (!window.confirm('Are you sure you want to clear your cart?')) return;

        try {
            const response = await fetch('/cart/clear', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (response.ok) {
                fetchCart();
            }
        } catch (error) {
            console.error('Error clearing cart:', error);
        }
    };

    if (loading) {
        return <SpinnerOverlay visible />;
    }

    return (
        <PageContentBlock title='Shopping Cart'>
            {!cart || cart.items.length === 0 ? (
                <div css={tw`text-center py-12`}>
                    <FontAwesomeIcon icon={faShoppingCart} css={tw`text-6xl text-neutral-500 mb-4`} />
                    <h2 css={tw`text-2xl font-bold mb-4`}>Your cart is empty</h2>
                    <p css={tw`text-neutral-400 mb-6`}>Add some hosting plans to get started!</p>
                    <button
                        onClick={() => history.push('/')}
                        css={tw`bg-cyan-500 hover:bg-cyan-600 text-white py-3 px-6 rounded font-semibold transition-colors`}
                    >
                        Browse Plans
                    </button>
                </div>
            ) : (
                <div css={tw`grid grid-cols-1 lg:grid-cols-3 gap-6`}>
                    <div css={tw`lg:col-span-2`}>
                        <div css={tw`bg-neutral-700 rounded-lg p-6`}>
                            <div css={tw`flex justify-between items-center mb-6`}>
                                <h2 css={tw`text-2xl font-bold`}>Cart Items ({cart.item_count})</h2>
                                <button
                                    onClick={clearCart}
                                    css={tw`text-red-400 hover:text-red-300 text-sm`}
                                >
                                    Clear Cart
                                </button>
                            </div>

                            <div css={tw`space-y-4`}>
                                {cart.items.map((item) => (
                                    <div key={item.id} css={tw`bg-neutral-800 rounded-lg p-4`}>
                                        <div css={tw`flex justify-between items-start`}>
                                            <div css={tw`flex-1`}>
                                                <h3 css={tw`text-lg font-bold mb-1`}>{item.plan.name}</h3>
                                                <p css={tw`text-sm text-neutral-400`}>
                                                    ${item.price_at_time} / {item.plan.billing_period}
                                                </p>
                                            </div>

                                            <div css={tw`flex items-center space-x-4`}>
                                                <div css={tw`flex items-center space-x-2`}>
                                                    <button
                                                        onClick={() => updateQuantity(item.id, item.quantity - 1)}
                                                        css={tw`bg-neutral-600 hover:bg-neutral-500 w-8 h-8 rounded`}
                                                    >
                                                        -
                                                    </button>
                                                    <span css={tw`w-8 text-center font-semibold`}>{item.quantity}</span>
                                                    <button
                                                        onClick={() => updateQuantity(item.id, item.quantity + 1)}
                                                        css={tw`bg-neutral-600 hover:bg-neutral-500 w-8 h-8 rounded`}
                                                    >
                                                        +
                                                    </button>
                                                </div>

                                                <div css={tw`text-right min-w-24`}>
                                                    <p css={tw`font-bold text-lg`}>${item.subtotal.toFixed(2)}</p>
                                                </div>

                                                <button
                                                    onClick={() => removeItem(item.id)}
                                                    css={tw`text-red-400 hover:text-red-300`}
                                                >
                                                    <FontAwesomeIcon icon={faTrash} />
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>

                    <div css={tw`lg:col-span-1`}>
                        <div css={tw`bg-neutral-700 rounded-lg p-6 sticky top-6`}>
                            <h3 css={tw`text-xl font-bold mb-4`}>Order Summary</h3>

                            <div css={tw`space-y-3 mb-6`}>
                                <div css={tw`flex justify-between`}>
                                    <span css={tw`text-neutral-400`}>Subtotal:</span>
                                    <span css={tw`font-semibold`}>${cart.total.toFixed(2)}</span>
                                </div>
                                <div css={tw`flex justify-between`}>
                                    <span css={tw`text-neutral-400`}>Tax:</span>
                                    <span css={tw`font-semibold`}>$0.00</span>
                                </div>
                                <div css={tw`border-t border-neutral-600 pt-3`}>
                                    <div css={tw`flex justify-between text-lg`}>
                                        <span css={tw`font-bold`}>Total:</span>
                                        <span css={tw`font-bold text-cyan-400`}>${cart.total.toFixed(2)}</span>
                                    </div>
                                </div>
                            </div>

                            <button
                                onClick={() => history.push('/checkout')}
                                css={tw`w-full bg-cyan-500 hover:bg-cyan-600 text-white py-3 px-4 rounded font-semibold transition-colors`}
                            >
                                Proceed to Checkout
                            </button>

                            <button
                                onClick={() => history.push('/')}
                                css={tw`w-full mt-3 bg-neutral-600 hover:bg-neutral-500 text-white py-3 px-4 rounded font-semibold transition-colors`}
                            >
                                Continue Shopping
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </PageContentBlock>
    );
};

export default CartPage;

