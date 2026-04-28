import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import api from '../api/axios';

export default function Reports() {
    const navigate = useNavigate();
    const [activeReport, setActiveReport] = useState('gst-summary');
    const [month, setMonth] = useState(new Date().toISOString().slice(0, 7));
    const [customerId, setCustomerId] = useState('');
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');

    const fetchReport = async () => {
        setLoading(true);
        setError('');
        setData(null);
        try {
            let url = '';
            if (activeReport === 'gst-summary') url = `/reports/gst-summary?month=${month}`;
            else if (activeReport === 'gstr-3b') url = `/reports/gstr-3b-summary?month=${month}`;
            else if (activeReport === 'itc-summary') url = `/reports/itc-summary?month=${month}`;
            else if (activeReport === 'pending-payments') url = `/reports/pending-payments`;
            else if (activeReport === 'sales') url = `/reports/sales?month=${month}`;
            else if (activeReport === 'customer-ledger') url = `/reports/customer-ledger?customer_id=${customerId}`;

            const res = await api.get(url);
            setData(res.data);
        } catch (err) {
            setError(err.response?.data?.message || 'Failed to load report');
        } finally {
            setLoading(false);
        }
    };

    const reports = [
        { id: 'gst-summary', label: '📊 GST Summary', desc: 'Monthly GST collection summary' },
        { id: 'gstr-3b', label: '📋 GSTR-3B', desc: 'GSTR-3B format report' },
        { id: 'itc-summary', label: '💰 ITC Summary', desc: 'Input Tax Credit summary' },
        { id: 'pending-payments', label: '⏳ Pending Payments', desc: 'Overdue invoice aging' },
        { id: 'sales', label: '📈 Sales Report', desc: 'Monthly sales report' },
        { id: 'customer-ledger', label: '📒 Customer Ledger', desc: 'Customer wise ledger' },
    ];

    const renderReport = () => {
        if (!data) return null;

        if (activeReport === 'gst-summary') {
            return (
                <div style={styles.reportResult}>
                    <h3 style={styles.resultTitle}>GST Summary — {month}</h3>
                    <div style={styles.summaryGrid}>
                        {[
                            { label: 'Invoice Count', value: data.invoice_count || 0, prefix: '' },
                            { label: 'Total Taxable', value: data.total_taxable || 0, prefix: '₹' },
                            { label: 'Total CGST', value: data.total_cgst || 0, prefix: '₹' },
                            { label: 'Total SGST', value: data.total_sgst || 0, prefix: '₹' },
                            { label: 'Total IGST', value: data.total_igst || 0, prefix: '₹' },
                            { label: 'Grand Total', value: data.grand_total || 0, prefix: '₹' },
                        ].map(item => (
                            <div key={item.label} style={styles.summaryCard}>
                                <p style={styles.summaryLabel}>{item.label}</p>
                                <p style={styles.summaryValue}>{item.prefix}{Number(item.value).toLocaleString('en-IN')}</p>
                            </div>
                        ))}
                    </div>
                </div>
            );
        }

        if (activeReport === 'gstr-3b') {
            return (
                <div style={styles.reportResult}>
                    <h3 style={styles.resultTitle}>GSTR-3B Summary — {month}</h3>
                    <div style={styles.gstr3bGrid}>
                        <div style={styles.gstr3bCard}>
                            <h4 style={styles.gstr3bTitle}>3.1(a) Outward Taxable Supplies</h4>
                            <p>Taxable: ₹{Number(data['3_1_a']?.taxable || 0).toLocaleString('en-IN')}</p>
                            <p>CGST: ₹{Number(data['3_1_a']?.cgst || 0).toLocaleString('en-IN')}</p>
                            <p>SGST: ₹{Number(data['3_1_a']?.sgst || 0).toLocaleString('en-IN')}</p>
                            <p>IGST: ₹{Number(data['3_1_a']?.igst || 0).toLocaleString('en-IN')}</p>
                        </div>
                        <div style={styles.gstr3bCard}>
                            <h4 style={styles.gstr3bTitle}>3.1(b) Zero Rated Supplies</h4>
                            <p>Taxable: ₹{Number(data['3_1_b']?.taxable || 0).toLocaleString('en-IN')}</p>
                        </div>
                        <div style={styles.gstr3bCard}>
                            <h4 style={styles.gstr3bTitle}>4A(5) ITC Available</h4>
                            <p>CGST: ₹{Number(data['4_A_5_itc']?.cgst || 0).toLocaleString('en-IN')}</p>
                            <p>SGST: ₹{Number(data['4_A_5_itc']?.sgst || 0).toLocaleString('en-IN')}</p>
                            <p>IGST: ₹{Number(data['4_A_5_itc']?.igst || 0).toLocaleString('en-IN')}</p>
                        </div>
                    </div>
                </div>
            );
        }

        if (activeReport === 'itc-summary') {
            return (
                <div style={styles.reportResult}>
                    <h3 style={styles.resultTitle}>ITC Summary — {month}</h3>
                    <div style={styles.summaryGrid}>
                        {[
                            { label: 'Eligible CGST', value: data.eligible?.cgst || 0, prefix: '₹' },
                            { label: 'Eligible SGST', value: data.eligible?.sgst || 0, prefix: '₹' },
                            { label: 'Eligible IGST', value: data.eligible?.igst || 0, prefix: '₹' },
                            { label: 'Total Eligible', value: data.eligible?.total || 0, prefix: '₹' },
                            { label: 'Blocked ITC', value: data.blocked?.total || 0, prefix: '₹' },
                        ].map(item => (
                            <div key={item.label} style={styles.summaryCard}>
                                <p style={styles.summaryLabel}>{item.label}</p>
                                <p style={styles.summaryValue}>{item.prefix}{Number(item.value).toLocaleString('en-IN')}</p>
                            </div>
                        ))}
                    </div>
                </div>
            );
        }

        if (activeReport === 'pending-payments') {
            return (
                <div style={styles.reportResult}>
                    <h3 style={styles.resultTitle}>Pending Payments</h3>
                    {data.length === 0 ? (
                        <p style={styles.noData}>No pending payments! ✅</p>
                    ) : (
                        <table style={styles.table}>
                            <thead>
                                <tr>
                                    {['Invoice No','Customer','Date','Due Date','Amount','Days Overdue','Aging'].map(h => (
                                        <th key={h} style={styles.th}>{h}</th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {data.map((inv, i) => (
                                    <tr key={inv.id} style={i%2===0?styles.trEven:styles.trOdd}>
                                        <td style={styles.td}>{inv.invoice_number}</td>
                                        <td style={styles.td}>{inv.customer?.name}</td>
                                        <td style={styles.td}>{inv.invoice_date}</td>
                                        <td style={styles.td}>{inv.due_date}</td>
                                        <td style={styles.td}>₹{Number(inv.total_amount).toLocaleString('en-IN')}</td>
                                        <td style={styles.td}>{inv.days_overdue} days</td>
                                        <td style={styles.td}>{inv.aging_bucket}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </div>
            );
        }

        if (activeReport === 'sales') {
            return (
                <div style={styles.reportResult}>
                    <h3 style={styles.resultTitle}>Sales Report — {month}</h3>
                    {data.length === 0 ? (
                        <p style={styles.noData}>No sales for this month</p>
                    ) : (
                        <table style={styles.table}>
                            <thead>
                                <tr>
                                    {['Invoice No','Customer','Date','Subtotal','CGST','SGST','IGST','Total','Status'].map(h => (
                                        <th key={h} style={styles.th}>{h}</th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {data.map((inv, i) => (
                                    <tr key={inv.id} style={i%2===0?styles.trEven:styles.trOdd}>
                                        <td style={styles.td}>{inv.invoice_number}</td>
                                        <td style={styles.td}>{inv.customer?.name}</td>
                                        <td style={styles.td}>{inv.invoice_date}</td>
                                        <td style={styles.td}>₹{Number(inv.subtotal).toLocaleString('en-IN')}</td>
                                        <td style={styles.td}>₹{Number(inv.cgst_total).toLocaleString('en-IN')}</td>
                                        <td style={styles.td}>₹{Number(inv.sgst_total).toLocaleString('en-IN')}</td>
                                        <td style={styles.td}>₹{Number(inv.igst_total).toLocaleString('en-IN')}</td>
                                        <td style={styles.td}>₹{Number(inv.total_amount).toLocaleString('en-IN')}</td>
                                        <td style={styles.td}>{inv.status}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </div>
            );
        }

        if (activeReport === 'customer-ledger') {
            return (
                <div style={styles.reportResult}>
                    <h3 style={styles.resultTitle}>Customer Ledger</h3>
                    {data.length === 0 ? (
                        <p style={styles.noData}>No invoices for this customer</p>
                    ) : (
                        <table style={styles.table}>
                            <thead>
                                <tr>
                                    {['Invoice No','Date','Due Date','Total','Status'].map(h => (
                                        <th key={h} style={styles.th}>{h}</th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {data.map((inv, i) => (
                                    <tr key={inv.id} style={i%2===0?styles.trEven:styles.trOdd}>
                                        <td style={styles.td}>{inv.invoice_number}</td>
                                        <td style={styles.td}>{inv.invoice_date}</td>
                                        <td style={styles.td}>{inv.due_date}</td>
                                        <td style={styles.td}>₹{Number(inv.total_amount).toLocaleString('en-IN')}</td>
                                        <td style={styles.td}>{inv.status}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </div>
            );
        }
    };

    return (
        <div style={styles.container}>
            <div style={styles.navbar}>
                <h1 style={styles.navTitle}>📊 GST Billing System</h1>
                <button onClick={() => navigate('/dashboard')} style={styles.backBtn}>← Dashboard</button>
            </div>

            <div style={styles.content}>
                <h2 style={styles.title}>📊 Reports</h2>

                {/* Report Selector */}
                <div style={styles.reportTabs}>
                    {reports.map(r => (
                        <div key={r.id} onClick={() => { setActiveReport(r.id); setData(null); }}
                            style={{...styles.reportTab, ...(activeReport === r.id ? styles.reportTabActive : {})}}>
                            <p style={styles.reportTabLabel}>{r.label}</p>
                            <p style={styles.reportTabDesc}>{r.desc}</p>
                        </div>
                    ))}
                </div>

                {/* Filters */}
                <div style={styles.filterCard}>
                    {activeReport !== 'pending-payments' && activeReport !== 'customer-ledger' && (
                        <div style={styles.filterField}>
                            <label style={styles.label}>Month</label>
                            <input type="month" style={styles.input} value={month} onChange={e => setMonth(e.target.value)} />
                        </div>
                    )}
                    {activeReport === 'customer-ledger' && (
                        <div style={styles.filterField}>
                            <label style={styles.label}>Customer ID</label>
                            <input type="number" style={styles.input} value={customerId} onChange={e => setCustomerId(e.target.value)} placeholder="Enter customer ID" />
                        </div>
                    )}
                    <button onClick={fetchReport} style={styles.fetchBtn} disabled={loading}>
                        {loading ? 'Loading...' : '🔍 Generate Report'}
                    </button>
                </div>

                {error && <div style={styles.error}>{error}</div>}

                {/* Report Results */}
                {renderReport()}
            </div>
        </div>
    );
}

const styles = {
    container: { minHeight: '100vh', backgroundColor: '#f0f2f5', fontFamily: 'Arial, sans-serif' },
    navbar: { backgroundColor: '#2C3E50', padding: '15px 30px', display: 'flex', justifyContent: 'space-between', alignItems: 'center' },
    navTitle: { color: '#fff', margin: 0, fontSize: '20px' },
    backBtn: { backgroundColor: '#3498DB', color: '#fff', border: 'none', padding: '8px 16px', borderRadius: '6px', cursor: 'pointer' },
    content: { padding: '30px' },
    title: { color: '#2C3E50', margin: '0 0 25px 0', fontSize: '24px' },
    reportTabs: { display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '15px', marginBottom: '25px' },
    reportTab: { backgroundColor: '#fff', borderRadius: '10px', padding: '15px', cursor: 'pointer', boxShadow: '0 2px 8px rgba(0,0,0,0.06)', border: '2px solid transparent' },
    reportTabActive: { border: '2px solid #2C3E50', backgroundColor: '#EBF5FB' },
    reportTabLabel: { color: '#2C3E50', fontWeight: 'bold', margin: '0 0 5px 0', fontSize: '14px' },
    reportTabDesc: { color: '#7F8C8D', margin: 0, fontSize: '12px' },
    filterCard: { backgroundColor: '#fff', borderRadius: '10px', padding: '20px', marginBottom: '20px', boxShadow: '0 2px 8px rgba(0,0,0,0.06)', display: 'flex', alignItems: 'flex-end', gap: '20px' },
    filterField: { display: 'flex', flexDirection: 'column', gap: '5px' },
    label: { fontSize: '13px', fontWeight: '600', color: '#2C3E50' },
    input: { padding: '10px', borderRadius: '6px', border: '1px solid #ddd', fontSize: '14px' },
    fetchBtn: { backgroundColor: '#2C3E50', color: '#fff', border: 'none', padding: '10px 25px', borderRadius: '6px', cursor: 'pointer', fontSize: '14px', fontWeight: 'bold' },
    error: { backgroundColor: '#FDEDEC', color: '#C0392B', padding: '12px', borderRadius: '8px', marginBottom: '15px' },
    reportResult: { backgroundColor: '#fff', borderRadius: '10px', padding: '25px', boxShadow: '0 2px 8px rgba(0,0,0,0.06)' },
    resultTitle: { color: '#2C3E50', margin: '0 0 20px 0', borderBottom: '2px solid #f0f2f5', paddingBottom: '10px' },
    summaryGrid: { display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '15px' },
    summaryCard: { backgroundColor: '#f8f9fa', borderRadius: '8px', padding: '15px', textAlign: 'center' },
    summaryLabel: { color: '#7F8C8D', fontSize: '12px', margin: '0 0 8px 0' },
    summaryValue: { color: '#2C3E50', fontSize: '20px', fontWeight: 'bold', margin: 0 },
    gstr3bGrid: { display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '15px' },
    gstr3bCard: { backgroundColor: '#f8f9fa', borderRadius: '8px', padding: '15px' },
    gstr3bTitle: { color: '#2C3E50', margin: '0 0 10px 0', fontSize: '13px' },
    noData: { textAlign: 'center', padding: '30px', color: '#7F8C8D' },
    table: { width: '100%', borderCollapse: 'collapse' },
    th: { backgroundColor: '#2C3E50', color: '#fff', padding: '10px 12px', textAlign: 'left', fontSize: '12px' },
    td: { padding: '8px 12px', fontSize: '12px', color: '#333' },
    trEven: { backgroundColor: '#f8f9fa' },
    trOdd: { backgroundColor: '#fff' },
};