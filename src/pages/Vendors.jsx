import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import api from '../api/axios';

export default function Vendors() {
    const navigate = useNavigate();
    const [vendors, setVendors] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showForm, setShowForm] = useState(false);
    const [editVendor, setEditVendor] = useState(null);
    const [form, setForm] = useState({
        name: '', gstin: '', email: '', phone: '',
        address: '', state_code: '', pan: '', contact_person: ''
    });
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');

    useEffect(() => { fetchVendors(); }, []);

    const fetchVendors = async () => {
        try {
            const res = await api.get('/vendors');
            setVendors(res.data);
        } catch (err) {
            setError('Failed to load vendors');
        } finally {
            setLoading(false);
        }
    };

    const resetForm = () => {
        setForm({ name:'', gstin:'', email:'', phone:'', address:'', state_code:'', pan:'', contact_person:'' });
        setEditVendor(null);
        setShowForm(false);
        setError('');
        setSuccess('');
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError(''); setSuccess('');
        try {
            if (editVendor) {
                await api.put(`/vendors/${editVendor.id}`, form);
                setSuccess('Vendor updated successfully!');
            } else {
                await api.post('/vendors', form);
                setSuccess('Vendor created successfully!');
            }
            resetForm();
            fetchVendors();
        } catch (err) {
            setError(err.response?.data?.message || 'Something went wrong');
        }
    };

    const handleEdit = (vendor) => {
        setEditVendor(vendor);
        setForm({
            name: vendor.name || '', gstin: vendor.gstin || '',
            email: vendor.email || '', phone: vendor.phone || '',
            address: vendor.address || '', state_code: vendor.state_code || '',
            pan: vendor.pan || '', contact_person: vendor.contact_person || ''
        });
        setShowForm(true);
    };

    const handleDelete = async (id) => {
        if (!window.confirm('Delete this vendor?')) return;
        try {
            await api.delete(`/vendors/${id}`);
            setSuccess('Vendor deleted!');
            fetchVendors();
        } catch (err) {
            setError('Failed to delete vendor');
        }
    };

    return (
        <div style={styles.container}>
            <div style={styles.navbar}>
                <h1 style={styles.navTitle}>📊 GST Billing System</h1>
                <div style={styles.navBtns}>
                    <button onClick={() => navigate('/dashboard')} style={styles.backBtn}>← Dashboard</button>
                </div>
            </div>

            <div style={styles.content}>
                <div style={styles.header}>
                    <h2 style={styles.title}>🏪 Vendors</h2>
                    <button onClick={() => { resetForm(); setShowForm(true); }} style={styles.addBtn}>
                        + Add Vendor
                    </button>
                </div>

                {error && <div style={styles.error}>{error}</div>}
                {success && <div style={styles.success}>{success}</div>}

                {showForm && (
                    <div style={styles.formCard}>
                        <h3 style={styles.formTitle}>{editVendor ? 'Edit Vendor' : 'Add New Vendor'}</h3>
                        <form onSubmit={handleSubmit}>
                            <div style={styles.formGrid}>
                                <div style={styles.field}>
                                    <label style={styles.label}>Name *</label>
                                    <input style={styles.input} value={form.name} onChange={e => setForm({...form, name: e.target.value})} required placeholder="Vendor name" />
                                </div>
                                <div style={styles.field}>
                                    <label style={styles.label}>GSTIN</label>
                                    <input style={styles.input} value={form.gstin} onChange={e => setForm({...form, gstin: e.target.value})} placeholder="29ABCDE1234F1Z5" />
                                </div>
                                <div style={styles.field}>
                                    <label style={styles.label}>Email</label>
                                    <input style={styles.input} type="email" value={form.email} onChange={e => setForm({...form, email: e.target.value})} placeholder="vendor@email.com" />
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
                                    <label style={styles.label}>PAN</label>
                                    <input style={styles.input} value={form.pan} onChange={e => setForm({...form, pan: e.target.value})} placeholder="ABCDE1234F" />
                                </div>
                                <div style={styles.field}>
                                    <label style={styles.label}>Contact Person</label>
                                    <input style={styles.input} value={form.contact_person} onChange={e => setForm({...form, contact_person: e.target.value})} placeholder="Contact person name" />
                                </div>
                                <div style={styles.field}>
                                    <label style={styles.label}>Address</label>
                                    <input style={styles.input} value={form.address} onChange={e => setForm({...form, address: e.target.value})} placeholder="Full address" />
                                </div>
                            </div>
                            <div style={styles.formBtns}>
                                <button type="submit" style={styles.saveBtn}>
                                    {editVendor ? 'Update Vendor' : 'Save Vendor'}
                                </button>
                                <button type="button" onClick={resetForm} style={styles.cancelBtn}>Cancel</button>
                            </div>
                        </form>
                    </div>
                )}

                {loading ? (
                    <div style={styles.loading}>Loading vendors...</div>
                ) : (
                    <div style={styles.tableCard}>
                        <table style={styles.table}>
                            <thead>
                                <tr>
                                    {['#','Name','GSTIN','Email','Phone','State','Contact','Actions'].map(h => (
                                        <th key={h} style={styles.th}>{h}</th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {vendors.length === 0 ? (
                                    <tr><td colSpan="8" style={styles.noData}>No vendors found</td></tr>
                                ) : vendors.map((v, i) => (
                                    <tr key={v.id} style={i%2===0?styles.trEven:styles.trOdd}>
                                        <td style={styles.td}>{i+1}</td>
                                        <td style={styles.td}>{v.name}</td>
                                        <td style={styles.td}>{v.gstin || 'N/A'}</td>
                                        <td style={styles.td}>{v.email || 'N/A'}</td>
                                        <td style={styles.td}>{v.phone || 'N/A'}</td>
                                        <td style={styles.td}>{v.state_code || 'N/A'}</td>
                                        <td style={styles.td}>{v.contact_person || 'N/A'}</td>
                                        <td style={styles.td}>
                                            <button onClick={() => handleEdit(v)} style={styles.editBtn}>Edit</button>
                                            <button onClick={() => handleDelete(v.id)} style={styles.deleteBtn}>Delete</button>
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
    navBtns: { display: 'flex', gap: '10px' },
    backBtn: { backgroundColor: '#3498DB', color: '#fff', border: 'none', padding: '8px 16px', borderRadius: '6px', cursor: 'pointer' },
    content: { padding: '30px' },
    header: { display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' },
    title: { color: '#2C3E50', margin: 0, fontSize: '24px' },
    addBtn: { backgroundColor: '#2ECC71', color: '#fff', border: 'none', padding: '10px 20px', borderRadius: '8px', cursor: 'pointer', fontSize: '14px', fontWeight: 'bold' },
    error: { backgroundColor: '#FDEDEC', color: '#C0392B', padding: '12px', borderRadius: '8px', marginBottom: '15px' },
    success: { backgroundColor: '#D5F5E3', color: '#1E8449', padding: '12px', borderRadius: '8px', marginBottom: '15px' },
    formCard: { backgroundColor: '#fff', borderRadius: '10px', padding: '25px', marginBottom: '25px', boxShadow: '0 2px 10px rgba(0,0,0,0.06)' },
    formTitle: { color: '#2C3E50', margin: '0 0 20px 0' },
    formGrid: { display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '15px', marginBottom: '15px' },
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