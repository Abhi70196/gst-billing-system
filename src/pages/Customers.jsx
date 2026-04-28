import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import api from '../api/axios';

export default function Customers() {
    const navigate = useNavigate();
    const [customers, setCustomers] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showForm, setShowForm] = useState(false);
    const [editCustomer, setEditCustomer] = useState(null);
    const [form, setForm] = useState({
        name: '', gstin: '', email: '', phone: '',
        billing_address: '', state_code: ''
    });
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');

    useEffect(() => { fetchCustomers(); }, []);

    const fetchCustomers = async () => {
        try {
            const res = await api.get('/customers');
            setCustomers(res.data);
        } catch (err) {
            setError('Failed to load customers');
        } finally {
            setLoading(false);
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError(''); setSuccess('');
        try {
            if (editCustomer) {
                await api.put(`/customers/${editCustomer.id}`, form);
                setSuccess('Customer updated successfully!');
            } else {
                await api.post('/customers', form);
                setSuccess('Customer created successfully!');
            }
            setShowForm(false);
            setEditCustomer(null);
            setForm({ name:'', gstin:'', email:'', phone:'', billing_address:'', state_code:'' });
            fetchCustomers();
        } catch (err) {
            setError(err.response?.data?.message || 'Something went wrong');
        }
    };

    const handleEdit = (customer) => {
        setEditCustomer(customer);
        setForm({
            name: customer.name, gstin: customer.gstin,
            email: customer.email, phone: customer.phone,
            billing_address: customer.billing_address,
            state_code: customer.state_code
        });
        setShowForm(true);
    };

    const handleDelete = async (id) => {
        if (!window.confirm('Are you sure you want to delete this customer?')) return;
        try {
            await api.delete(`/customers/${id}`);
            setSuccess('Customer deleted!');
            fetchCustomers();
        } catch (err) {
            setError('Failed to delete customer');
        }
    };

    return (
        <div style={styles.container}>
            {/* Navbar */}
            <div style={styles.navbar}>
                <h1 style={styles.navTitle}>📊 GST Billing System</h1>
                <button onClick={() => navigate('/dashboard')} style={styles.backBtn}>
                    ← Dashboard
                </button>
            </div>

            <div style={styles.content}>
                {/* Header */}
                <div style={styles.header}>
                    <h2 style={styles.title}>👥 Customers</h2>
                    <button onClick={() => { setShowForm(true); setEditCustomer(null); setForm({ name:'', gstin:'', email:'', phone:'', billing_address:'', state_code:'' }); }} style={styles.addBtn}>
                        + Add Customer
                    </button>
                </div>

                {/* Messages */}
                {error && <div style={styles.error}>{error}</div>}
                {success && <div style={styles.success}>{success}</div>}

                {/* Add/Edit Form */}
                {showForm && (
                    <div style={styles.formCard}>
                        <h3 style={styles.formTitle}>{editCustomer ? 'Edit Customer' : 'Add New Customer'}</h3>
                        <form onSubmit={handleSubmit} style={styles.form}>
                            <div style={styles.formGrid}>
                                <div style={styles.field}>
                                    <label style={styles.label}>Name *</label>
                                    <input style={styles.input} value={form.name} onChange={e => setForm({...form, name: e.target.value})} required placeholder="Customer name" />
                                </div>
                                <div style={styles.field}>
                                    <label style={styles.label}>GSTIN</label>
                                    <input style={styles.input} value={form.gstin} onChange={e => setForm({...form, gstin: e.target.value})} placeholder="29ABCDE1234F1Z5" />
                                </div>
                                <div style={styles.field}>
                                    <label style={styles.label}>Email</label>
                                    <input style={styles.input} type="email" value={form.email} onChange={e => setForm({...form, email: e.target.value})} placeholder="email@example.com" />
                                </div>
                                <div style={styles.field}>
                                    <label style={styles.label}>Phone</label>
                                    <input style={styles.input} value={form.phone} onChange={e => setForm({...form, phone: e.target.value})} placeholder="9876543210" />
                                </div>
                                <div style={styles.field}>
                                    <label style={styles.label}>State Code</label>
                                    <input style={styles.input} value={form.state_code} onChange={e => setForm({...form, state_code: e.target.value})} placeholder="29" />
                                </div>
                                <div style={styles.field}>
                                    <label style={styles.label}>Billing Address</label>
                                    <input style={styles.input} value={form.billing_address} onChange={e => setForm({...form, billing_address: e.target.value})} placeholder="Full address" />
                                </div>
                            </div>
                            <div style={styles.formBtns}>
                                <button type="submit" style={styles.saveBtn}>
                                    {editCustomer ? 'Update Customer' : 'Save Customer'}
                                </button>
                                <button type="button" onClick={() => setShowForm(false)} style={styles.cancelBtn}>
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                )}

                {/* Customers Table */}
                {loading ? (
                    <div style={styles.loading}>Loading customers...</div>
                ) : (
                    <div style={styles.tableCard}>
                        <table style={styles.table}>
                            <thead>
                                <tr>
                                    {['#','Name','GSTIN','Email','Phone','State','Actions'].map(h => (
                                        <th key={h} style={styles.th}>{h}</th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {customers.length === 0 ? (
                                    <tr><td colSpan="7" style={styles.noData}>No customers found</td></tr>
                                ) : customers.map((c, i) => (
                                    <tr key={c.id} style={i%2===0?styles.trEven:styles.trOdd}>
                                        <td style={styles.td}>{i+1}</td>
                                        <td style={styles.td}>{c.name}</td>
                                        <td style={styles.td}>{c.gstin || 'N/A'}</td>
                                        <td style={styles.td}>{c.email || 'N/A'}</td>
                                        <td style={styles.td}>{c.phone || 'N/A'}</td>
                                        <td style={styles.td}>{c.state_code}</td>
                                        <td style={styles.td}>
                                            <button onClick={() => handleEdit(c)} style={styles.editBtn}>Edit</button>
                                            <button onClick={() => handleDelete(c.id)} style={styles.deleteBtn}>Delete</button>
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
    form: { display: 'flex', flexDirection: 'column', gap: '15px' },
    formGrid: { display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '15px' },
    field: { display: 'flex', flexDirection: 'column', gap: '5px' },
    label: { fontSize: '13px', fontWeight: '600', color: '#2C3E50' },
    input: { padding: '10px', borderRadius: '6px', border: '1px solid #ddd', fontSize: '14px' },
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
    editBtn: { backgroundColor: '#3498DB', color: '#fff', border: 'none', padding: '5px 12px', borderRadius: '4px', cursor: 'pointer', marginRight: '5px', fontSize: '12px' },
    deleteBtn: { backgroundColor: '#E74C3C', color: '#fff', border: 'none', padding: '5px 12px', borderRadius: '4px', cursor: 'pointer', fontSize: '12px' },
};