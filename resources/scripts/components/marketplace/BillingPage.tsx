import React, { useEffect, useState } from 'react';
import tw from 'twin.macro';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faFileInvoice, faShoppingBag, faCheckCircle, faTimesCircle } from '@fortawesome/free-solid-svg-icons';
import PageContentBlock from '@/components/elements/PageContentBlock';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';

interface Order {
    id: number;
    uuid: string;
    order_number: string;
    total: number;
    currency: string;
    status: string;
    created_at: string;
    completed_at: string | null;
    server: {
        id: number;
        uuid: string;
        name: string;
    } | null;
}

interface Invoice {
    id: number;
    uuid: string;
    invoice_number: string;
    total: number;
    currency: string;
    status: string;
    due_date: string | null;
    created_at: string;
    paid_at: string | null;
    is_overdue: boolean;
}

const BillingPage: React.FC = () => {
    const [orders, setOrders] = useState<Order[]>([]);
    const [invoices, setInvoices] = useState<Invoice[]>([]);
    const [loading, setLoading] = useState(true);
    const [activeTab, setActiveTab] = useState<'orders' | 'invoices'>('orders');

    useEffect(() => {
        const fetchData = async () => {
            try {
                const [ordersResponse, invoicesResponse] = await Promise.all([
                    fetch('/billing/orders'),
                    fetch('/billing/invoices'),
                ]);

                const ordersData = await ordersResponse.json();
                const invoicesData = await invoicesResponse.json();

                setOrders(ordersData.data);
                setInvoices(invoicesData.data);
            } catch (error) {
                console.error('Error fetching billing data:', error);
            } finally {
                setLoading(false);
            }
        };

        fetchData();
    }, []);

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'completed':
            case 'paid':
                return tw`text-green-400`;
            case 'pending':
            case 'draft':
                return tw`text-yellow-400`;
            case 'failed':
            case 'cancelled':
                return tw`text-red-400`;
            default:
                return tw`text-neutral-400`;
        }
    };

    const getStatusIcon = (status: string) => {
        switch (status) {
            case 'completed':
            case 'paid':
                return faCheckCircle;
            case 'failed':
            case 'cancelled':
                return faTimesCircle;
            default:
                return faFileInvoice;
        }
    };

    if (loading) {
        return <SpinnerOverlay visible />;
    }

    return (
        <PageContentBlock title='Billing'>
            <div css={tw`mb-6`}>
                <div css={tw`flex space-x-4 border-b border-neutral-700`}>
                    <button
                        onClick={() => setActiveTab('orders')}
                        css={[
                            tw`py-3 px-6 font-semibold transition-colors`,
                            activeTab === 'orders'
                                ? tw`border-b-2 border-cyan-500 text-cyan-500`
                                : tw`text-neutral-400 hover:text-neutral-200`,
                        ]}
                    >
                        <FontAwesomeIcon icon={faShoppingBag} css={tw`mr-2`} />
                        Orders ({orders.length})
                    </button>
                    <button
                        onClick={() => setActiveTab('invoices')}
                        css={[
                            tw`py-3 px-6 font-semibold transition-colors`,
                            activeTab === 'invoices'
                                ? tw`border-b-2 border-cyan-500 text-cyan-500`
                                : tw`text-neutral-400 hover:text-neutral-200`,
                        ]}
                    >
                        <FontAwesomeIcon icon={faFileInvoice} css={tw`mr-2`} />
                        Invoices ({invoices.length})
                    </button>
                </div>
            </div>

            {activeTab === 'orders' && (
                <div css={tw`space-y-4`}>
                    {orders.length === 0 ? (
                        <div css={tw`text-center py-12 bg-neutral-700 rounded-lg`}>
                            <FontAwesomeIcon icon={faShoppingBag} css={tw`text-6xl text-neutral-500 mb-4`} />
                            <p css={tw`text-neutral-400`}>No orders yet</p>
                        </div>
                    ) : (
                        orders.map((order) => (
                            <div key={order.id} css={tw`bg-neutral-700 rounded-lg p-6`}>
                                <div css={tw`flex justify-between items-start mb-4`}>
                                    <div>
                                        <h3 css={tw`text-xl font-bold mb-1`}>Order #{order.order_number}</h3>
                                        <p css={tw`text-sm text-neutral-400`}>
                                            {new Date(order.created_at).toLocaleDateString()}
                                        </p>
                                    </div>
                                    <div css={tw`text-right`}>
                                        <div css={tw`text-2xl font-bold text-cyan-400 mb-1`}>
                                            ${order.total.toFixed(2)}
                                        </div>
                                        <div css={[tw`inline-flex items-center`, getStatusColor(order.status)]}>
                                            <FontAwesomeIcon icon={getStatusIcon(order.status)} css={tw`mr-2`} />
                                            <span css={tw`capitalize font-semibold`}>{order.status}</span>
                                        </div>
                                    </div>
                                </div>

                                {order.server && (
                                    <div css={tw`bg-neutral-800 rounded p-4 mt-4`}>
                                        <p css={tw`text-sm text-neutral-400 mb-1`}>Server Created:</p>
                                        <p css={tw`font-semibold`}>{order.server.name}</p>
                                        <a
                                            href={`/server/${order.server.uuid}`}
                                            css={tw`text-cyan-400 hover:text-cyan-300 text-sm mt-2 inline-block`}
                                        >
                                            View Server →
                                        </a>
                                    </div>
                                )}
                            </div>
                        ))
                    )}
                </div>
            )}

            {activeTab === 'invoices' && (
                <div css={tw`space-y-4`}>
                    {invoices.length === 0 ? (
                        <div css={tw`text-center py-12 bg-neutral-700 rounded-lg`}>
                            <FontAwesomeIcon icon={faFileInvoice} css={tw`text-6xl text-neutral-500 mb-4`} />
                            <p css={tw`text-neutral-400`}>No invoices yet</p>
                        </div>
                    ) : (
                        invoices.map((invoice) => (
                            <div key={invoice.id} css={tw`bg-neutral-700 rounded-lg p-6`}>
                                <div css={tw`flex justify-between items-start`}>
                                    <div>
                                        <h3 css={tw`text-xl font-bold mb-1`}>Invoice #{invoice.invoice_number}</h3>
                                        <p css={tw`text-sm text-neutral-400`}>
                                            {new Date(invoice.created_at).toLocaleDateString()}
                                        </p>
                                        {invoice.due_date && (
                                            <p css={tw`text-sm text-neutral-400`}>
                                                Due: {new Date(invoice.due_date).toLocaleDateString()}
                                            </p>
                                        )}
                                    </div>
                                    <div css={tw`text-right`}>
                                        <div css={tw`text-2xl font-bold text-cyan-400 mb-1`}>
                                            ${invoice.total.toFixed(2)}
                                        </div>
                                        <div css={[tw`inline-flex items-center`, getStatusColor(invoice.status)]}>
                                            <FontAwesomeIcon icon={getStatusIcon(invoice.status)} css={tw`mr-2`} />
                                            <span css={tw`capitalize font-semibold`}>{invoice.status}</span>
                                        </div>
                                        {invoice.is_overdue && <div css={tw`text-red-400 text-sm mt-1`}>Overdue</div>}
                                    </div>
                                </div>
                            </div>
                        ))
                    )}
                </div>
            )}
        </PageContentBlock>
    );
};

export default BillingPage;
