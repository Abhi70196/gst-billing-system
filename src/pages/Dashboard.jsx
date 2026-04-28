import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import api from '../api/axios';

export default function Dashboard() {
    const { user, logout } = useAuth();
    const navigate = useNavigate();
    const [stats, setStats] = useState(null);
    const [loading, setLoading] = useState(true);
    const month = new Date().toISOString().slice(0, 7);

    useEffect(() => {
        api.get(`/dashboard/summary?month=${month}`)
            .then(res => setStats(res.data))
            .catch(err => console.error(err))
            .finally(() => setLoading(false));
    }, []);

    return (
        <div style={styles.container}>
            {/* Navbar */}
            <div style={styles.navbar}>
                <h1 style={styles.navTitle}>📊 GST Billing System</h1>
                <div style={styles.navRight}>
                    <span style={styles.userName}>👤 {user?.name}</span>
                    <span style={styles.role}>{user?.role}</span>
                    <button onClick={logout} style={styles.logoutBtn}>Logout</button>
                </div>
            </div>

            {/* Sidebar + Content */}
            <div style={styles.layout}>
                {/* Sidebar */}
                <div style={styles.sidebar}>
                    <nav>
                        {[
                            { icon: '🏠', label: 'Dashboard', path: '/dashboard' },
                            { icon: '👥', label: 'Customers', path: '/customers' },
                            { icon: '📦', label: 'Products', path: '/products' },
                            { icon: '🧾', label: 'Invoices', path: '/invoices' },
                            { icon: '🏪', label: 'Vendors', path: '/vendors' },
                            { icon: '📋', label: 'Purchase Bills', path: '/purchase-bills' },
                            { icon: '📊', label: 'Reports', path: '/reports' },
                        ].map(item => (
                            <div
                                key={item.path}
                                onClick={() => navigate(item.path)}
                                style={styles.navItem}
                            >
                                <span>{item.icon}</span>
                                <span>{item.label}</span>
                            </div>
                        ))}
                    </nav>
                </div>

                {/* Main Content */}
                <div style={styles.main}>
                    <h2 style={styles.pageTitle}>Dashboard</h2>
                    <p style={styles.monthLabel}>📅 {month}</p>

                    {loading ? (
                        <div style={styles.loading}>Loading dashboard data...</div>
                    ) : (
                        <>
                            {/* Stats Cards */}
                            <div style={styles.statsGrid}>
                                <div style={{...styles.statCard, borderTop: '4px solid #3498DB'}}>
                                    <p style={styles.statLabel}>Total Sales</p>
                                    <p style={styles.statValue}>
                                        ₹{Number(stats?.sales?.total_sales || 0).toLocaleString('en-IN')}
                                    </p>
                                    <p style={styles.statSub}>{stats?.sales?.invoice_count || 0} Invoices</p>
                                </div>

                                <div style={{...styles.statCard, borderTop: '4px solid #2ECC71'}}>
                                    <p style={styles.statLabel}>IGST Collected</p>
                                    <p style={styles.statValue}>
                                        ₹{Number(stats?.sales?.total_igst || 0).toLocaleString('en-IN')}
                                    </p>
                                    <p style={styles.statSub}>Integrated GST</p>
                                </div>

                                <div style={{...styles.statCard, borderTop: '4px solid #E74C3C'}}>
                                    <p style={styles.statLabel}>Pending Collections</p>
                                    <p style={styles.statValue}>
                                        ₹{Number(stats?.pending?.amount || 0).toLocaleString('en-IN')}
                                    </p>
                                    <p style={styles.statSub}>{stats?.pending?.count || 0} Invoices</p>
                                </div>

                                <div style={{...styles.statCard, borderTop: '4px solid #F39C12'}}>
                                    <p style={styles.statLabel}>ITC Available</p>
                                    <p style={styles.statValue}>
                                        ₹{Number(stats?.itc?.total_itc || 0).toLocaleString('en-IN')}
                                    </p>
                                    <p style={styles.statSub}>Input Tax Credit</p>
                                </div>
                            </div>

                            {/* GST Breakdown */}
                            <div style={styles.section}>
                                <h3 style={styles.sectionTitle}>GST Breakdown</h3>
                                <div style={styles.gstGrid}>
                                    <div style={styles.gstCard}>
                                        <p style={styles.gstLabel}>Taxable Amount</p>
                                        <p style={styles.gstValue}>₹{Number(stats?.sales?.total_sales || 0).toLocaleString('en-IN')}</p>
                                    </div>
                                    <div style={styles.gstCard}>
                                        <p style={styles.gstLabel}>CGST</p>
                                        <p style={styles.gstValue}>₹{Number(stats?.sales?.total_cgst || 0).toLocaleString('en-IN')}</p>
                                    </div>
                                    <div style={styles.gstCard}>
                                        <p style={styles.gstLabel}>SGST</p>
                                        <p style={styles.gstValue}>₹{Number(stats?.sales?.total_sgst || 0).toLocaleString('en-IN')}</p>
                                    </div>
                                    <div style={styles.gstCard}>
                                        <p style={styles.gstLabel}>IGST</p>
                                        <p style={styles.gstValue}>₹{Number(stats?.sales?.total_igst || 0).toLocaleString('en-IN')}</p>
                                    </div>
                                </div>
                            </div>

                            {/* Top Customers */}
                            <div style={styles.section}>
                                <h3 style={styles.sectionTitle}>Top Customers</h3>
                                {stats?.top_customers?.length > 0 ? (
                                    <table style={styles.table}>
                                        <thead>
                                            <tr>
                                                <th style={styles.th}>#</th>
                                                <th style={styles.th}>Customer Name</th>
                                                <th style={styles.th}>GSTIN</th>
                                                <th style={styles.th}>Total Sales</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {stats.top_customers.map((c, i) => (
                                                <tr key={c.customer_id} style={i%2===0?styles.trEven:styles.trOdd}>
                                                    <td style={styles.td}>{i + 1}</td>
                                                    <td style={styles.td}>{c.customer?.name}</td>
                                                    <td style={styles.td}>{c.customer?.gstin}</td>
                                                    <td style={styles.td}>₹{Number(c.total).toLocaleString('en-IN')}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                ) : (
                                    <p style={styles.noData}>No customer data for this month</p>
                                )}
                            </div>
                        </>
                    )}
                </div>
            </div>
        </div>
    );
}

const styles = {
    container: { minHeight: '100vh', backgroundColor: '#f0f2f5', fontFamily: 'Arial, sans-serif' },
    navbar: { backgroundColor: '#2C3E50', padding: '15px 30px', display: 'flex', justifyContent: 'space-between', alignItems: 'center' },
    navTitle: { color: '#fff', margin: 0, fontSize: '20px' },
    navRight: { display: 'flex', alignItems: 'center', gap: '15px' },
    userName: { color: '#ECF0F1', fontSize: '14px' },
    role: { backgroundColor: '#3498DB', color: '#fff', padding: '3px 10px', borderRadius: '12px', fontSize: '12px' },
    logoutBtn: { backgroundColor: '#E74C3C', color: '#fff', border: 'none', padding: '8px 16px', borderRadius: '6px', cursor: 'pointer' },
    layout: { display: 'flex', minHeight: 'calc(100vh - 60px)' },
    sidebar: { width: '220px', backgroundColor: '#2C3E50', padding: '20px 0' },
    navItem: { display: 'flex', alignItems: 'center', gap: '12px', padding: '12px 20px', color: '#BDC3C7', cursor: 'pointer', fontSize: '14px', transition: 'all 0.2s' },
    main: { flex: 1, padding: '30px', overflowY: 'auto' },
    pageTitle: { fontSize: '24px', color: '#2C3E50', margin: '0 0 5px 0' },
    monthLabel: { color: '#7F8C8D', fontSize: '14px', marginBottom: '25px' },
    loading: { textAlign: 'center', padding: '50px', color: '#7F8C8D', fontSize: '16px' },
    statsGrid: { display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: '20px', marginBottom: '30px' },
    statCard: { backgroundColor: '#fff', borderRadius: '10px', padding: '20px', boxShadow: '0 2px 10px rgba(0,0,0,0.06)' },
    statLabel: { color: '#7F8C8D', fontSize: '13px', margin: '0 0 8px 0' },
    statValue: { color: '#2C3E50', fontSize: '24px', fontWeight: 'bold', margin: '0 0 5px 0' },
    statSub: { color: '#BDC3C7', fontSize: '12px', margin: 0 },
    section: { backgroundColor: '#fff', borderRadius: '10px', padding: '20px', marginBottom: '25px', boxShadow: '0 2px 10px rgba(0,0,0,0.06)' },
    sectionTitle: { color: '#2C3E50', fontSize: '16px', margin: '0 0 20px 0', borderBottom: '2px solid #f0f2f5', paddingBottom: '10px' },
    gstGrid: { display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: '15px' },
    gstCard: { backgroundColor: '#f8f9fa', borderRadius: '8px', padding: '15px', textAlign: 'center' },
    gstLabel: { color: '#7F8C8D', fontSize: '12px', margin: '0 0 8px 0' },
    gstValue: { color: '#2C3E50', fontSize: '18px', fontWeight: 'bold', margin: 0 },
    table: { width: '100%', borderCollapse: 'collapse' },
    th: { backgroundColor: '#2C3E50', color: '#fff', padding: '10px 15px', textAlign: 'left', fontSize: '13px' },
    td: { padding: '10px 15px', fontSize: '13px', color: '#333' },
    trEven: { backgroundColor: '#f8f9fa' },
    trOdd: { backgroundColor: '#fff' },
    noData: { color: '#7F8C8D', textAlign: 'center', padding: '20px' },
};