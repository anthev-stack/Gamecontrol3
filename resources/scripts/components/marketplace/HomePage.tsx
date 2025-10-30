import React, { useEffect, useState } from 'react';
import tw from 'twin.macro';
import styled from 'styled-components';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faShoppingCart, faStar } from '@fortawesome/free-solid-svg-icons';
import PageContentBlock from '@/components/elements/PageContentBlock';
import { httpErrorToHuman } from '@/api/http';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';

interface Plan {
    id: number;
    name: string;
    description: string;
    slug: string;
    memory: number;
    disk: number;
    cpu: number;
    price: number;
    billing_period: string;
    is_featured: boolean;
    is_available: boolean;
    nest: { id: number; name: string };
    egg: { id: number; name: string };
}

const PlanCard = styled.div<{ featured?: boolean }>`
    ${tw`bg-neutral-700 rounded-lg p-6 shadow-md hover:shadow-xl transition-shadow duration-200`}
    ${(props) => props.featured && tw`border-2 border-cyan-500`}
`;

const HomePage: React.FC = () => {
    const [plans, setPlans] = useState<Plan[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');

    useEffect(() => {
        fetch('/plans')
            .then((response) => response.json())
            .then((data) => {
                setPlans(data.data);
                setLoading(false);
            })
            .catch((error) => {
                console.error('Error fetching plans:', error);
                setError(httpErrorToHuman(error));
                setLoading(false);
            });
    }, []);

    const addToCart = async (planId: number) => {
        try {
            const response = await fetch('/cart/add', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ plan_id: planId, quantity: 1 }),
            });

            if (response.ok) {
                alert('Added to cart!');
            } else {
                const data = await response.json();
                alert(data.error || 'Failed to add to cart');
            }
        } catch (error) {
            console.error('Error adding to cart:', error);
            alert('Failed to add to cart');
        }
    };

    if (loading) {
        return <SpinnerOverlay visible />;
    }

    return (
        <PageContentBlock title='Hosting Plans'>
            {error && <div css={tw`bg-red-500 text-white p-4 rounded mb-4`}>{error}</div>}

            <div css={tw`mb-8 text-center`}>
                <h1 css={tw`text-4xl font-bold mb-4`}>Choose Your Perfect Hosting Plan</h1>
                <p css={tw`text-neutral-300 text-lg`}>
                    Powerful game server hosting with automatic setup and 24/7 support
                </p>
            </div>

            <div css={tw`grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6`}>
                {plans.map((plan) => (
                    <PlanCard key={plan.id} featured={plan.is_featured}>
                        {plan.is_featured && (
                            <div css={tw`flex items-center justify-center mb-2`}>
                                <FontAwesomeIcon icon={faStar} css={tw`text-yellow-400 mr-2`} />
                                <span css={tw`text-yellow-400 font-bold`}>Featured</span>
                            </div>
                        )}

                        <h3 css={tw`text-2xl font-bold mb-2`}>{plan.name}</h3>
                        <p css={tw`text-neutral-400 mb-4`} style={{ minHeight: '3rem' }}>{plan.description}</p>

                        <div css={tw`mb-4`}>
                            <div css={tw`text-3xl font-bold text-cyan-400`}>
                                ${plan.price}
                                <span css={tw`text-base text-neutral-400 font-normal`}>/{plan.billing_period}</span>
                            </div>
                        </div>

                        <div css={tw`space-y-2 mb-6`}>
                            <div css={tw`flex justify-between text-sm`}>
                                <span css={tw`text-neutral-400`}>Memory:</span>
                                <span css={tw`font-semibold`}>{plan.memory}MB</span>
                            </div>
                            <div css={tw`flex justify-between text-sm`}>
                                <span css={tw`text-neutral-400`}>Disk:</span>
                                <span css={tw`font-semibold`}>{plan.disk}MB</span>
                            </div>
                            <div css={tw`flex justify-between text-sm`}>
                                <span css={tw`text-neutral-400`}>CPU:</span>
                                <span css={tw`font-semibold`}>{plan.cpu}%</span>
                            </div>
                            <div css={tw`flex justify-between text-sm`}>
                                <span css={tw`text-neutral-400`}>Game:</span>
                                <span css={tw`font-semibold`}>{plan.egg.name}</span>
                            </div>
                        </div>

                        <button
                            onClick={() => addToCart(plan.id)}
                            disabled={!plan.is_available}
                            css={[
                                tw`w-full py-3 px-4 rounded font-semibold transition-colors duration-200`,
                                plan.is_available
                                    ? tw`bg-cyan-500 hover:bg-cyan-600 text-white`
                                    : tw`bg-neutral-600 text-neutral-400 cursor-not-allowed`,
                            ]}
                        >
                            {plan.is_available ? (
                                <>
                                    <FontAwesomeIcon icon={faShoppingCart} css={tw`mr-2`} />
                                    Add to Cart
                                </>
                            ) : (
                                'Out of Stock'
                            )}
                        </button>
                    </PlanCard>
                ))}
            </div>

            {plans.length === 0 && (
                <div css={tw`text-center py-12`}>
                    <p css={tw`text-neutral-400 text-lg`}>No hosting plans available at this time.</p>
                </div>
            )}
        </PageContentBlock>
    );
};

export default HomePage;
