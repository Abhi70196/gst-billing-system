import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import api from '../api/axios';

export default function PurchaseBills() {
    const navigate = useNavigate();
    const [bills, setBills] = useState([]);
    const [vendors, setVendors] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showForm, setShowForm] = useState(false);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');
    const [form, setForm] = useState({
        vendor_id: '', vendor_bill_number: '',
        date: '', due_date: '', notes: '',
        items: [{ product_name: '', hsn_sac: '', quantity: '', unit_price: '', gst_rate: '18', discount: '0' }]
    });

    useEffect(() => {
        fetchBills();
        fetchVendors();
    }, []);

    const fetchBills = async () => {
        try {
            const res = await api.get('/purchase-bills');
            setBills(res.data);
        } catch (err) {
            setError('Failed to load purchase bills');
        } finally {
            setLoading(false);
        }
    };

    const fetchVendors = async () => {
        try {
            const res = await api.get('/vendors');
            setVendors(res.data);
        } catch (err) {}
    };

    const addItem = () => {
        setForm({ ...form, items: [...form.items, { product_name: '', hsn_sac: '', quantity: '', unit_price: '', gst_rate: '18', discount: '0' }] });
    };

    const removeItem = (index) => {
        const items = form.items.filter((_, i) => i !== index);
        setForm({ ...form, items });
    };

    const updateItem = (index, field, value) => {
        const items = [...form.items];
        items[index][field] = value;
        setForm({ ...form, items });
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError(''); setSuccess('');
        try {
            await api.post('/purchase-bills', form);
            setSuccess('Purchase bill created successfully!');
            setShowForm(false);
            setForm({ vendor_id:'', vendor_bill_number:'', date:'', due_date:'', notes:'',
                items: [{ product_name:'', hsn_sac:'', quantity:'', unit_price:'', gst_rate:'18', discount:'0' }] });
            fetchBills();
        } catch (err) {
            setError(err.response?.data?.message || 'Something went wrong');
        }
    };

    const getStatusColor = (status) => {
        const colors = { unpaid: '#E74C3C', partial: '#F39C12', paid: '#2ECC71', cancelled: '#95A5A6' };
        return colors[status] || '#333';
    };

    return (
        <div style={styles.container}>
            <div style={styles.navbar}>
                <h1 style={styles.navTitle}>📊 GST Billing System</h1>
                <button onClick={() => navigate('/dashboard')} style={styles.backBtn}>← Dashboard</button>
            </div>

            <div style={styles.content}>
                <div style={styles.header}>
                    <h2 style={styles.title}>📋 Purchase Bills</h2>
                    <button onClick={() => setShowForm(!showForm)} style={styles.addBtn}>
                        {showForm ? 'Cancel' : '+ New Purchase Bill'}
                    </button>
                </div>

                {error && <div style={styles.error}>{error}</div>}
                {success && <div style={styles.success}>{success}</div>}

                {/* Create Form */}
                {showForm && (
                    <div style={styles.formCard}>
                        <h3 style={styles.formTitle}>New Purchase Bill</h3>
                        <form onSubmit={handleSubmit}>
                            {/* Bill Details */}
                            <div style={styles.formGrid}>
                                <div style={styles.field}>
                                    <label style={styles.label}>Vendor *</label>
                                    <select style={styles.input} value={form.vendor_id} onChange={e => setForm({...form, vendor_id: e.target.value})} required>
                                        <option value="">Select Vendor</option>
                                        {vendors.map(v => <option key={v.id} value={v.id}>{v.name}</option>)}
                                    </select>
                                </div>
                                <div style={styles.field}>
                                    <label style={styles.label}>Vendor Bill Number</label>
                                    <input style={styles.input} value={form.vendor_bill_number} onChange={e => setForm({...form, vendor_bill_number: e.target.value})} placeholder="VB-001" />
                                </div>
                                <div style={styles.field}>
                                    <label style={styles.label}>Bill Date *</label>
                                    <input style={styles.input} type="date" value={form.date} onChange={e => setForm({...form, date: e.target.value})} required />
                                </div>
                                <div style={styles.field}>
                                    <label style={styles.label}>Due Date</label>
                                    <input style={styles.input} type="date" value={form.due_date} onChange={e => setForm({...form, due_date: e.target.value})} />
                                </div>
                                <div style={styles.field}>
                                    <label style={styles.label}>Notes</label>
                                    <input style={styles.input} value={form.notes} onChange={e => setForm({...form, notes: e.target.value})} placeholder="Optional notes" />
                                </div>
                            </div>

                            {/* Items */}
                            <div style={styles.itemsSection}>
                                <div style={styles.itemsHeader}>
                                    <h4 style={styles.itemsTitle}>Items</h4>
                                    <button type="button" onClick={addItem} style={styles.addItemBtn}>+ Add Item</button>
                                </div>

                                <table style={styles.itemTable}>
                                    <thead>
                                        <tr>
                                            {['Product Name','HSN/SAC','Qty','Unit Price','GST%','Discount%',''].map(h => (
                                                <th key={h} style={styles.itemTh}>{h}</th>
                                            ))}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {form.items.map((item, i) => (
                                            <tr key={i}>
                                                <td style={styles.itemTd}><input style={styles.itemInput} value={item.product_name} onChange={e => updateItem(i, 'product_name', e.target.value)} placeholder="Product name" required /></td>
                                                <td style={styles.itemTd}><input style={styles.itemInput} value={item.hsn_sac} onChange={e => updateItem(i, 'hsn_sac', e.target.value)} placeholder="HSN code" /></td>
                                                <td style={styles.itemTd}><input style={styles.itemInput} type="number" value={item.quantity} onChange={e => updateItem(i, 'quantity', e.target.value)} placeholder="0" required /></td>
                                                <td style={styles.itemTd}><input style={styles.itemInput} type="number" value={item.unit_price} onChange={e => updateItem(i, 'unit_price', e.target.value)} placeholder="0.00" required /></td>
                                                <td style={styles.itemTd}>
                                                    <select style={styles.itemInput} value={item.gst_rate} onChange={e => updateItem(i, 'gst_rate', e.target.value)}>
                                                        {['0','5','12','18','28'].map(r => <option key={r} value={r}>{r}%</option>)}
                                                    </select>
                                                </td>
                                                <td style={styles.itemTd}><input style={styles.itemInput} type="number" value={item.discount} onChange={e => updateItem(i, 'discount', e.target.value)} placeholder="0" /></td>
                                                <td style={styles.itemTd}>
                                                    {form.items.length > 1 && (
                                                        <button type="button" onClick={() => removeItem(i)} style={styles.removeBtn}>✕</button>
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            <div style={styles.formBtns}>
                                <button type="submit" style={styles.saveBtn}>Create Purchase Bill</button>
                                <button type="button" onClick={() => setShowForm(false)} style={styles.cancelBtn}>Cancel</button>
                            </div>
                        </form>
                    </div>
                )}

                {/* Bills Table */}
                {loading ? (
                    <div style={styles.loading}>Loading purchase bills...</div>
                ) : (
                    <div style={styles.tableCard}>
                        <table style={styles.table}>
                            <thead>
                                <tr>
                                    {['#','Bill No','Vendor','Date','Due Date','Total','Paid','Balance','Status'].map(h => (
                                        <th key={h} style={styles.th}>{h}</th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {bills.length === 0 ? (
                                    <tr><td colSpan="9" style={styles.noData}>No purchase bills found</td></tr>
                                ) : bills.map((b, i) => (
                                    <tr key={b.id} style={i%2===0?styles.trEven:styles.trOdd}>
                                        <td style={styles.td}>{i+1}</td>
                                        <td style={styles.td}>{b.bill_number}</td>
                                        <td style={styles.td}>{b.vendor?.name}</td>
                                        <td style={styles.td}>{b.date}</td>
                                        <td style={styles.td}>{b.due_date || 'N/A'}</td>
                                        <td style={styles.td}>₹{Number(b.total_amount).toLocaleString('en-IN')}</td>
                                        <td style={styles.td}>₹{Number(b.paid_amount).toLocaleString('en-IN')}</td>
                                        <td style={styles.td}>₹{Number(b.balance_due).toLocaleString('en-IN')}</td>
                                        <td style={styles.td}>
                                            <span style={{...styles.badge, backgroundColor: getStatusColor(b.status)}}>
                                                {b.status}
                                            </span>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
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
    header: { display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' },
    title: { color: '#2C3E50', margin: 0, fontSize: '24px' },
    addBtn: { backgroundColor: '#2ECC71', color: '#fff', border: 'none', padding: '10px 20px', borderRadius: '8px', cursor: 'pointer', fontSize: '14px', fontWeight: 'bold' },
    error: { backgroundColor: '#FDEDEC', color: '#C0392B', padding: '12px', borderRadius: '8px', marginBottom: '15px' },
    success: { backgroundColor: '#D5F5E3', color: '#1E8449', padding: '12px', borderRadius: '8px', marginBottom: '15px' },
    formCard: { backgroundColor: '#fff', borderRadius: '10px', padding: '25px', marginBottom: '25px', boxShadow: '0 2px 10px rgba(0,0,0,0.06)' },
    formTitle: { color: '#2C3E50', margin: '0 0 20px 0' },
    formGrid: { display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '15px', marginBottom: '20px' },
    field: { display: 'flex', flexDirection: 'column', gap: '5px' },
    label: { fontSize: '13px', fontWeight: '600', color: '#2C3E50' },
    input: { padding: '10px', borderRadius: '6px', border: '1px solid #ddd', fontSize: '14px' },
    itemsSection: { marginBottom: '20px' },
    itemsHeader: { display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '10px' },
    itemsTitle: { color: '#2C3E50', margin: 0 },
    addItemBtn: { backgroundColor: '#3498DB', color: '#fff', border: 'none', padding: '6px 14px', borderRadius: '6px', cursor: 'pointer', fontSize: '13px' },
    itemTable: { width: '100%', borderCollapse: 'collapse', border: '1px solid #ddd' },
    itemTh: { backgroundColor: '#f8f9fa', padding: '8px 10px', textAlign: 'left', fontSize: '12px', color: '#2C3E50', borderBottom: '1px solid #ddd' },
    itemTd: { padding: '6px 8px', borderBottom: '1px solid #f0f0f0' },
    itemInput: { width: '100%', padding: '6px', borderRadius: '4px', border: '1px solid #ddd', fontSize: '13px' },
    removeBtn: { backgroundColor: '#E74C3C', color: '#fff', border: 'none', padding: '4px 8px', borderRadius: '4px', cursor: 'pointer' },
    formBtns: { display: 'flex', gap: '10px' },
    saveBtn: { backgroundColor: '#2C3E50', color: '#fff', border: 'none', padding: '10px 25px', borderRadius: '6px', cursor: 'pointer', fontSize: '14px' },
    cancelBtn: { backgroundColor: '#95A5A6', color: '#fff', border: 'none', padding: '10px 25px', borderRadius: '6px', cursor: 'pointer', fontSize: '14px' },
    loading: { textAlign: 'center', padding: '50px', color: '#7F8C8D' },
    tableCard: { backgroundColor: '#fff', borderRadius: '10px', padding: '20px', boxShadow: '0 2px 10px rgba(0,0,0,0.06)', overflowX: 'auto' },
    table: { width: '100%', borderCollapse: 'collapse' },
    th: { backgroundColor: '#2C3E50', color: '#fff', padding: '12px 15px', textAlign: 'left', fontSize: '13px' },
    td: { padding: '10px 15px', fontSize: '13px', color: '#333' },
    trEven: { backgroundColor: '#f8f9fa' },
    trOdd: { backgroundColor: '#fff' },
    noData: { textAlign: 'center', padding: '30px', color: '#7F8C8D' },
    badge: { color: '#fff', padding: '3px 10px', borderRadius: '12px', fontSize: '11px', fontWeight: 'bold' },
};