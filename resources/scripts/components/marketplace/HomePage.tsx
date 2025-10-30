import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import tw from 'twin.macro';
import styled from 'styled-components';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faShoppingCart, faStar, faServer, faShieldAlt, faHeadset, faRocket } from '@fortawesome/free-solid-svg-icons';
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

const HeroSection = styled.div`
    ${tw`bg-gradient-to-r from-cyan-600 to-blue-700 text-white py-20 px-6`}
`;

const PlanCard = styled.div<{ featured?: boolean }>`
    ${tw`bg-neutral-800 rounded-lg p-6 shadow-md hover:shadow-xl transition-all duration-300 border-2`}
    ${(props) => (props.featured ? tw`border-cyan-500 transform scale-105` : tw`border-neutral-700`)}
`;

const FeatureCard = styled.div`
    ${tw`bg-neutral-800 rounded-lg p-8 text-center hover:bg-neutral-700 transition-colors duration-200`}
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
                alert('Added to cart! Visit /cart to checkout.');
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
        <div css={tw`min-h-screen bg-neutral-900 text-white`}>
            {/* Navigation */}
            <nav css={tw`bg-neutral-800 shadow-lg sticky top-0 z-50`}>
                <div css={tw`max-w-7xl mx-auto px-4 sm:px-6 lg:px-8`}>
                    <div css={tw`flex justify-between h-16 items-center`}>
                        <div css={tw`flex-shrink-0 flex items-center`}>
                            <Link to={'/'} css={tw`text-2xl font-bold text-cyan-400 no-underline hover:text-cyan-300`}>
                                GameControl
                            </Link>
                        </div>
                        <div css={tw`flex items-center space-x-4`}>
                            <Link
                                to={'/auth/login'}
                                css={tw`px-4 py-2 text-sm font-medium text-cyan-400 hover:text-cyan-300 transition-colors no-underline`}
                            >
                                Login
                            </Link>
                            <Link
                                to={'/auth/register'}
                                css={tw`px-6 py-2 bg-cyan-500 hover:bg-cyan-600 text-white rounded-lg text-sm font-semibold transition-colors no-underline`}
                            >
                                Sign Up
                            </Link>
                        </div>
                    </div>
                </div>
            </nav>

            {/* Hero Section */}
            <HeroSection>
                <div css={tw`max-w-7xl mx-auto text-center`}>
                    <h1 css={tw`text-5xl md:text-6xl font-bold mb-6`}>Premium Game Server Hosting</h1>
                    <p css={tw`text-xl md:text-2xl mb-8 text-cyan-100`}>
                        Lightning-fast servers, instant setup, and 24/7 support
                    </p>
                    <div css={tw`flex justify-center gap-4 flex-wrap`}>
                        <a
                            href='#plans'
                            css={tw`px-8 py-4 bg-white text-cyan-600 rounded-lg text-lg font-bold hover:bg-gray-100 transition-colors no-underline`}
                        >
                            View Plans
                        </a>
                        <Link
                            to={'/auth/register'}
                            css={tw`px-8 py-4 bg-cyan-500 hover:bg-cyan-400 text-white rounded-lg text-lg font-bold transition-colors no-underline`}
                        >
                            Get Started Free
                        </Link>
                    </div>
                </div>
            </HeroSection>

            {/* Features Section */}
            <section css={tw`py-16 px-6 bg-neutral-900`}>
                <div css={tw`max-w-7xl mx-auto`}>
                    <h2 css={tw`text-4xl font-bold text-center mb-12`}>Why Choose Us?</h2>
                    <div css={tw`grid grid-cols-1 md:grid-cols-3 gap-8`}>
                        <FeatureCard>
                            <FontAwesomeIcon icon={faRocket} css={tw`text-5xl text-cyan-400 mb-4`} />
                            <h3 css={tw`text-xl font-bold mb-3`}>Instant Deployment</h3>
                            <p css={tw`text-neutral-400`}>Your server is ready in seconds. No waiting, no hassle.</p>
                        </FeatureCard>
                        <FeatureCard>
                            <FontAwesomeIcon icon={faShieldAlt} css={tw`text-5xl text-cyan-400 mb-4`} />
                            <h3 css={tw`text-xl font-bold mb-3`}>DDoS Protection</h3>
                            <p css={tw`text-neutral-400`}>Enterprise-grade DDoS protection keeps your server online.</p>
                        </FeatureCard>
                        <FeatureCard>
                            <FontAwesomeIcon icon={faHeadset} css={tw`text-5xl text-cyan-400 mb-4`} />
                            <h3 css={tw`text-xl font-bold mb-3`}>24/7 Support</h3>
                            <p css={tw`text-neutral-400`}>Our expert team is always here to help you succeed.</p>
                        </FeatureCard>
                    </div>
                </div>
            </section>

            {/* Plans Section */}
            <section id='plans' css={tw`py-16 px-6 bg-neutral-800`}>
                <div css={tw`max-w-7xl mx-auto`}>
                    <div css={tw`text-center mb-12`}>
                        <h2 css={tw`text-4xl font-bold mb-4`}>Choose Your Perfect Plan</h2>
                        <p css={tw`text-neutral-300 text-lg`}>Powerful hosting with transparent pricing</p>
                    </div>

                    {error && <div css={tw`bg-red-500 text-white p-4 rounded mb-6 text-center`}>{error}</div>}

                    <div css={tw`grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8`}>
                        {plans.map((plan) => (
                            <PlanCard key={plan.id} featured={plan.is_featured}>
                                {plan.is_featured && (
                                    <div css={tw`text-center mb-3`}>
                                        <span css={tw`bg-cyan-500 text-white text-xs font-bold px-3 py-1 rounded-full`}>
                                            <FontAwesomeIcon icon={faStar} css={tw`mr-1`} />
                                            MOST POPULAR
                                        </span>
                                    </div>
                                )}

                                <h3 css={tw`text-2xl font-bold mb-2 text-center`}>{plan.name}</h3>
                                <p css={tw`text-neutral-400 mb-6 text-center text-sm`} style={{ minHeight: '3rem' }}>
                                    {plan.description}
                                </p>

                                <div css={tw`text-center mb-6`}>
                                    <div css={tw`text-4xl font-bold text-cyan-400`}>
                                        ${plan.price}
                                        <span css={tw`text-lg text-neutral-400 font-normal`}>
                                            /{plan.billing_period}
                                        </span>
                                    </div>
                                </div>

                                <div css={tw`space-y-3 mb-8`}>
                                    <div css={tw`flex justify-between text-sm border-b border-neutral-700 pb-2`}>
                                        <span css={tw`text-neutral-400`}>RAM:</span>
                                        <span css={tw`font-semibold text-white`}>{plan.memory} MB</span>
                                    </div>
                                    <div css={tw`flex justify-between text-sm border-b border-neutral-700 pb-2`}>
                                        <span css={tw`text-neutral-400`}>Storage:</span>
                                        <span css={tw`font-semibold text-white`}>{plan.disk} MB</span>
                                    </div>
                                    <div css={tw`flex justify-between text-sm border-b border-neutral-700 pb-2`}>
                                        <span css={tw`text-neutral-400`}>CPU:</span>
                                        <span css={tw`font-semibold text-white`}>{plan.cpu}%</span>
                                    </div>
                                    <div css={tw`flex justify-between text-sm border-b border-neutral-700 pb-2`}>
                                        <span css={tw`text-neutral-400`}>Game:</span>
                                        <span css={tw`font-semibold text-white`}>{plan.egg.name}</span>
                                    </div>
                                </div>

                                <button
                                    onClick={() => addToCart(plan.id)}
                                    disabled={!plan.is_available}
                                    css={[
                                        tw`w-full py-3 px-4 rounded-lg font-semibold transition-all duration-200 transform hover:scale-105`,
                                        plan.is_available
                                            ? tw`bg-cyan-500 hover:bg-cyan-600 text-white shadow-lg`
                                            : tw`bg-neutral-600 text-neutral-400 cursor-not-allowed`,
                                    ]}
                                >
                                    {plan.is_available ? (
                                        <>
                                            <FontAwesomeIcon icon={faShoppingCart} css={tw`mr-2`} />
                                            Order Now
                                        </>
                                    ) : (
                                        'Out of Stock'
                                    )}
                                </button>
                            </PlanCard>
                        ))}
                    </div>

                    {plans.length === 0 && (
                        <div css={tw`text-center py-16 bg-neutral-900 rounded-lg`}>
                            <FontAwesomeIcon icon={faServer} css={tw`text-6xl text-neutral-600 mb-4`} />
                            <p css={tw`text-neutral-400 text-lg mb-4`}>No hosting plans available yet</p>
                            <p css={tw`text-neutral-500 text-sm`}>Check back soon for amazing deals!</p>
                        </div>
                    )}
                </div>
            </section>

            {/* Footer */}
            <footer css={tw`bg-neutral-900 py-12 px-6 border-t border-neutral-800`}>
                <div css={tw`max-w-7xl mx-auto text-center`}>
                    <p css={tw`text-neutral-400`}>&copy; 2025 GameControl. All rights reserved.</p>
                    <div css={tw`mt-4 space-x-6`}>
                        <Link to={'/auth/login'} css={tw`text-neutral-500 hover:text-neutral-300 no-underline`}>
                            Login
                        </Link>
                        <Link to={'/auth/register'} css={tw`text-neutral-500 hover:text-neutral-300 no-underline`}>
                            Sign Up
                        </Link>
                        <a href='#' css={tw`text-neutral-500 hover:text-neutral-300 no-underline`}>
                            Terms
                        </a>
                        <a href='#' css={tw`text-neutral-500 hover:text-neutral-300 no-underline`}>
                            Privacy
                        </a>
                    </div>
                </div>
            </footer>
        </div>
    );
};

export default HomePage;
