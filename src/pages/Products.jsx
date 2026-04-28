import { useState, useEffect } from 'react';
import api from '../api/axios';

const GST_RATES = [0, 5, 12, 18, 28];
const UNITS = ['Nos', 'Pcs', 'Box', 'Kg', 'Gram', 'Litre', 'Ml', 'Meter', 'Feet', 'Set', 'Pair', 'Hour', 'Day'];

// ── Exact field names the backend expects & returns ──────────────────────────
const EMPTY_FORM = {
  name:         '',
  hsn_sac_code: '',   // ← correct backend field
  unit:         'Nos',
  unit_price:   '',
  gst_rate:     18,
  is_service:   0,
  is_exempt:    0,
};

const labelStyle = {
  display: 'block', fontSize: 12, fontWeight: 600,
  color: '#475569', marginBottom: 5,
};

const inputStyle = {
  width: '100%', padding: '9px 12px',
  border: '1px solid #e2e8f0', borderRadius: 7,
  fontSize: 14, color: '#1e293b', outline: 'none',
  boxSizing: 'border-box', fontFamily: 'inherit',
  background: '#fff',
};

export default function Products() {
  const [products, setProducts] = useState([]);
  const [loading, setLoading]   = useState(true);
  const [saving, setSaving]     = useState(false);
  const [error, setError]       = useState('');
  const [success, setSuccess]   = useState('');
  const [search, setSearch]     = useState('');
  const [showForm, setShowForm] = useState(false);
  const [isEdit, setIsEdit]     = useState(false);
  const [editId, setEditId]     = useState(null);
  const [form, setForm]         = useState({ ...EMPTY_FORM });

  const flash = (msg, isErr = false) => {
    if (isErr) { setError(msg);   setTimeout(() => setError(''),   4000); }
    else        { setSuccess(msg); setTimeout(() => setSuccess(''), 3000); }
  };

  const fetchProducts = async () => {
    setLoading(true);
    try {
      const res = await api.get('/products');
      const raw = res.data;
      if (Array.isArray(raw))           setProducts(raw);
      else if (Array.isArray(raw.data)) setProducts(raw.data);
      else                              setProducts([]);
    } catch (err) {
      flash(err.response?.data?.message || 'Failed to load products.', true);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => { fetchProducts(); }, []);

  // Client-side search
  const filtered = products.filter(p => {
    const q = search.toLowerCase();
    return (
      p.name?.toLowerCase().includes(q) ||
      p.hsn_sac_code?.toLowerCase().includes(q)
    );
  });

  const handleField = (key, val) => setForm(f => ({ ...f, [key]: val }));

  const openAdd = () => {
    setForm({ ...EMPTY_FORM });
    setIsEdit(false);
    setEditId(null);
    setShowForm(true);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const openEdit = (p) => {
    setForm({
      name:         p.name         || '',
      hsn_sac_code: p.hsn_sac_code || '',
      unit:         p.unit         || 'Nos',
      unit_price:   p.unit_price   || '',
      gst_rate:     parseInt(p.gst_rate) || 18,
      is_service:   p.is_service   ?? 0,
      is_exempt:    p.is_exempt    ?? 0,
    });
    setIsEdit(true);
    setEditId(p.id);
    setShowForm(true);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const closeForm = () => { setShowForm(false); setError(''); };

  const handleSave = async (e) => {
    e.preventDefault();
    setSaving(true);
    try {
      if (isEdit) {
        await api.put(`/products/${editId}`, form);
        flash('Product updated successfully!');
      } else {
        await api.post('/products', form);
        flash('Product added successfully!');
      }
      setShowForm(false);
      fetchProducts();
    } catch (err) {
      const errors = err.response?.data?.errors;
      const msg = errors
        ? Object.values(errors).flat().join(' | ')
        : err.response?.data?.message || 'Failed to save product.';
      flash(msg, true);
    } finally {
      setSaving(false);
    }
  };

  const handleDelete = async (p) => {
    if (!window.confirm(`Delete "${p.name}"? This cannot be undone.`)) return;
    try {
      await api.delete(`/products/${p.id}`);
      flash('Product deleted.');
      fetchProducts();
    } catch (err) {
      flash(err.response?.data?.message || 'Failed to delete.', true);
    }
  };

  const gstBadge = (rate) => {
    const r = parseInt(rate);
    const map = {
      0:  { bg: '#f1f5f9', color: '#475569' },
      5:  { bg: '#ecfdf5', color: '#059669' },
      12: { bg: '#eff6ff', color: '#3b82f6' },
      18: { bg: '#fefce8', color: '#b45309' },
      28: { bg: '#fef2f2', color: '#dc2626' },
    };
    return map[r] || { bg: '#f1f5f9', color: '#475569' };
  };

  // Live GST preview
  const price   = parseFloat(form.unit_price) || 0;
  const gstAmt  = price * form.gst_rate / 100;
  const halfGst = gstAmt / 2;
  const total   = price + gstAmt;

  return (
    <div style={{ minHeight: '100vh', background: '#f1f5f9', fontFamily: 'system-ui,sans-serif' }}>

      {/* Navbar */}
      <div style={{ background: '#1e293b', padding: '0 32px', height: 56, display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
        <span style={{ color: '#f8fafc', fontWeight: 700, fontSize: 18 }}>📦 GST Billing System</span>
        <a href="/dashboard" style={{ color: '#94a3b8', fontSize: 13, textDecoration: 'none' }}>← Dashboard</a>
      </div>

      <div style={{ maxWidth: 1100, margin: '0 auto', padding: '32px 24px' }}>

        {/* Flash messages */}
        {success && (
          <div style={{ background: '#dcfce7', border: '1px solid #86efac', color: '#166534', padding: '10px 16px', borderRadius: 8, marginBottom: 16, fontSize: 14 }}>
            ✅ {success}
          </div>
        )}
        {error && (
          <div style={{ background: '#fee2e2', border: '1px solid #fca5a5', color: '#991b1b', padding: '10px 16px', borderRadius: 8, marginBottom: 16, fontSize: 14 }}>
            ❌ {error}
          </div>
        )}

        {/* Page header */}
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 24 }}>
          <div>
            <h1 style={{ margin: 0, fontSize: 24, fontWeight: 700, color: '#1e293b' }}>Products</h1>
            <p style={{ margin: '4px 0 0', fontSize: 13, color: '#64748b' }}>{products.length} total products</p>
          </div>
          <button
            onClick={openAdd}
            style={{ padding: '10px 20px', background: '#16a34a', color: '#fff', border: 'none', borderRadius: 8, fontSize: 14, fontWeight: 600, cursor: 'pointer' }}
          >
            + Add Product
          </button>
        </div>

        {/* Add / Edit Form */}
        {showForm && (
          <div style={{ background: '#fff', borderRadius: 12, padding: 28, marginBottom: 24, boxShadow: '0 4px 20px rgba(0,0,0,0.08)', border: '1px solid #e2e8f0' }}>
            <h2 style={{ margin: '0 0 24px', fontSize: 18, fontWeight: 700, color: '#1e293b' }}>
              {isEdit ? '✏️ Edit Product' : '➕ Add New Product'}
            </h2>

            <form onSubmit={handleSave}>

              {/* Row 1: Name, HSN, Unit */}
              <div style={{ display: 'grid', gridTemplateColumns: '2fr 1fr 1fr', gap: 16, marginBottom: 16 }}>
                <div>
                  <label style={labelStyle}>Product Name <span style={{ color: '#ef4444' }}>*</span></label>
                  <input
                    style={inputStyle} value={form.name} required
                    onChange={e => handleField('name', e.target.value)}
                    placeholder="e.g. Premium Steel Bolt"
                  />
                </div>
                <div>
                  <label style={labelStyle}>HSN / SAC Code</label>
                  <input
                    style={inputStyle} value={form.hsn_sac_code}
                    onChange={e => handleField('hsn_sac_code', e.target.value)}
                    placeholder="e.g. 7318"
                  />
                </div>
                <div>
                  <label style={labelStyle}>Unit <span style={{ color: '#ef4444' }}>*</span></label>
                  <select style={inputStyle} value={form.unit} onChange={e => handleField('unit', e.target.value)}>
                    {UNITS.map(u => <option key={u} value={u}>{u}</option>)}
                  </select>
                </div>
              </div>

              {/* Row 2: Unit Price */}
              <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr 1fr', gap: 16, marginBottom: 16 }}>
                <div>
                  <label style={labelStyle}>Unit Price (₹) <span style={{ color: '#ef4444' }}>*</span></label>
                  <input
                    type="number" min="0" step="0.01" style={inputStyle}
                    value={form.unit_price} required
                    onChange={e => handleField('unit_price', e.target.value)}
                    placeholder="0.00"
                  />
                </div>

                {/* Service / Exempt toggles */}
                <div style={{ display: 'flex', flexDirection: 'column', justifyContent: 'flex-end', paddingBottom: 2 }}>
                  <label style={{ display: 'flex', alignItems: 'center', gap: 8, cursor: 'pointer', fontSize: 14, color: '#475569' }}>
                    <input
                      type="checkbox"
                      checked={form.is_service === 1}
                      onChange={e => handleField('is_service', e.target.checked ? 1 : 0)}
                      style={{ width: 16, height: 16, accentColor: '#3b82f6' }}
                    />
                    This is a Service (SAC)
                  </label>
                </div>

                <div style={{ display: 'flex', flexDirection: 'column', justifyContent: 'flex-end', paddingBottom: 2 }}>
                  <label style={{ display: 'flex', alignItems: 'center', gap: 8, cursor: 'pointer', fontSize: 14, color: '#475569' }}>
                    <input
                      type="checkbox"
                      checked={form.is_exempt === 1}
                      onChange={e => handleField('is_exempt', e.target.checked ? 1 : 0)}
                      style={{ width: 16, height: 16, accentColor: '#3b82f6' }}
                    />
                    GST Exempt
                  </label>
                </div>
              </div>

              {/* GST Rate picker */}
              {form.is_exempt === 0 && (
                <div style={{ marginBottom: 16 }}>
                  <label style={labelStyle}>GST Rate <span style={{ color: '#ef4444' }}>*</span></label>
                  <div style={{ display: 'flex', gap: 10, marginTop: 6 }}>
                    {GST_RATES.map(rate => {
                      const active = form.gst_rate === rate;
                      return (
                        <button
                          key={rate} type="button"
                          onClick={() => handleField('gst_rate', rate)}
                          style={{
                            padding: '8px 22px', borderRadius: 7, fontSize: 14, fontWeight: 600,
                            cursor: 'pointer', border: '2px solid',
                            borderColor: active ? '#3b82f6' : '#e2e8f0',
                            background:  active ? '#3b82f6' : '#fff',
                            color:       active ? '#fff'    : '#64748b',
                            transition: 'all 0.15s',
                          }}
                        >
                          {rate}%
                        </button>
                      );
                    })}
                  </div>
                </div>
              )}

              {/* Live GST preview */}
              {price > 0 && form.is_exempt === 0 && (
                <div style={{ background: '#f0fdf4', border: '1px solid #86efac', borderRadius: 8, padding: '12px 16px', marginBottom: 16 }}>
                  <div style={{ fontSize: 12, fontWeight: 700, color: '#166534', marginBottom: 6 }}>
                    📊 GST Preview — Unit Price ₹{price.toFixed(2)} @ {form.gst_rate}%
                  </div>
                  <div style={{ display: 'flex', gap: 32, fontSize: 13, color: '#15803d' }}>
                    {form.gst_rate > 0 && (
                      <>
                        <span>CGST ({form.gst_rate / 2}%) = <strong>₹{halfGst.toFixed(2)}</strong></span>
                        <span>SGST ({form.gst_rate / 2}%) = <strong>₹{halfGst.toFixed(2)}</strong></span>
                      </>
                    )}
                    <span>Total with GST = <strong>₹{total.toFixed(2)}</strong></span>
                  </div>
                </div>
              )}

              {/* Buttons */}
              <div style={{ display: 'flex', gap: 10, marginTop: 8 }}>
                <button
                  type="submit" disabled={saving}
                  style={{ padding: '10px 24px', background: saving ? '#93c5fd' : '#3b82f6', color: '#fff', border: 'none', borderRadius: 8, fontSize: 14, fontWeight: 600, cursor: saving ? 'not-allowed' : 'pointer' }}
                >
                  {saving ? 'Saving…' : isEdit ? 'Update Product' : 'Save Product'}
                </button>
                <button
                  type="button" onClick={closeForm}
                  style={{ padding: '10px 20px', background: '#fff', color: '#64748b', border: '1px solid #e2e8f0', borderRadius: 8, fontSize: 14, cursor: 'pointer' }}
                >
                  Cancel
                </button>
              </div>

            </form>
          </div>
        )}

        {/* Search */}
        <div style={{ marginBottom: 16 }}>
          <input
            style={{ ...inputStyle, maxWidth: 360 }}
            value={search}
            onChange={e => setSearch(e.target.value)}
            placeholder="🔍  Search by name or HSN/SAC code…"
          />
        </div>

        {/* Table */}
        <div style={{ background: '#fff', borderRadius: 12, boxShadow: '0 1px 4px rgba(0,0,0,0.07)', overflow: 'hidden' }}>
          {loading ? (
            <div style={{ padding: 48, textAlign: 'center', color: '#94a3b8' }}>Loading products…</div>
          ) : filtered.length === 0 ? (
            <div style={{ padding: 48, textAlign: 'center', color: '#94a3b8' }}>
              {search ? 'No products match your search.' : 'No products yet. Click "+ Add Product" to start.'}
            </div>
          ) : (
            <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 14 }}>
              <thead>
                <tr style={{ background: '#1e293b' }}>
                  {['#', 'Name', 'HSN/SAC Code', 'Unit', 'Unit Price', 'GST Rate', 'Type', 'Actions'].map(h => (
                    <th key={h} style={{ padding: '12px 16px', textAlign: 'left', color: '#cbd5e1', fontSize: 12, fontWeight: 600, whiteSpace: 'nowrap' }}>
                      {h}
                    </th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {filtered.map((p, i) => {
                  const badge = gstBadge(p.gst_rate);
                  return (
                    <tr
                      key={p.id}
                      style={{ borderBottom: '1px solid #f1f5f9' }}
                      onMouseEnter={e => e.currentTarget.style.background = '#f8fafc'}
                      onMouseLeave={e => e.currentTarget.style.background = 'transparent'}
                    >
                      <td style={{ padding: '12px 16px', color: '#94a3b8' }}>{i + 1}</td>

                      <td style={{ padding: '12px 16px', fontWeight: 600, color: '#1e293b' }}>
                        {p.name}
                      </td>

                      <td style={{ padding: '12px 16px', fontFamily: 'monospace', fontSize: 13, color: '#475569' }}>
                        {p.hsn_sac_code || <span style={{ color: '#cbd5e1' }}>—</span>}
                      </td>

                      <td style={{ padding: '12px 16px', color: '#475569' }}>{p.unit}</td>

                      <td style={{ padding: '12px 16px', fontWeight: 600, color: '#1e293b' }}>
                        ₹{parseFloat(p.unit_price || 0).toFixed(2)}
                      </td>

                      <td style={{ padding: '12px 16px' }}>
                        {p.is_exempt ? (
                          <span style={{ background: '#f1f5f9', color: '#64748b', padding: '3px 10px', borderRadius: 20, fontSize: 12, fontWeight: 700 }}>
                            Exempt
                          </span>
                        ) : (
                          <span style={{ background: badge.bg, color: badge.color, padding: '3px 10px', borderRadius: 20, fontSize: 12, fontWeight: 700 }}>
                            {parseInt(p.gst_rate)}%
                          </span>
                        )}
                      </td>

                      <td style={{ padding: '12px 16px' }}>
                        <span style={{
                          background: p.is_service ? '#eff6ff' : '#f0fdf4',
                          color: p.is_service ? '#3b82f6' : '#16a34a',
                          padding: '3px 10px', borderRadius: 20, fontSize: 12, fontWeight: 600
                        }}>
                          {p.is_service ? 'Service' : 'Goods'}
                        </span>
                      </td>

                      <td style={{ padding: '12px 16px' }}>
                        <div style={{ display: 'flex', gap: 8 }}>
                          <button
                            onClick={() => openEdit(p)}
                            style={{ padding: '5px 14px', borderRadius: 6, border: '1px solid #3b82f6', background: '#eff6ff', color: '#3b82f6', fontSize: 12, fontWeight: 600, cursor: 'pointer' }}
                          >
                            Edit
                          </button>
                          <button
                            onClick={() => handleDelete(p)}
                            style={{ padding: '5px 14px', borderRadius: 6, border: '1px solid #ef4444', background: '#fef2f2', color: '#ef4444', fontSize: 12, fontWeight: 600, cursor: 'pointer' }}
                          >
                            Delete
                          </button>
                        </div>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          )}
        </div>

      </div>
    </div>
  );
}