import { useState, useEffect } from 'react';
import api from '../api/axios';

// ─── Constants ────────────────────────────────────────────────────────────────
const INDIAN_STATES = [
  { code: '01', name: 'Jammu & Kashmir' }, { code: '02', name: 'Himachal Pradesh' },
  { code: '03', name: 'Punjab' },          { code: '04', name: 'Chandigarh' },
  { code: '05', name: 'Uttarakhand' },     { code: '06', name: 'Haryana' },
  { code: '07', name: 'Delhi' },           { code: '08', name: 'Rajasthan' },
  { code: '09', name: 'Uttar Pradesh' },   { code: '10', name: 'Bihar' },
  { code: '11', name: 'Sikkim' },          { code: '12', name: 'Arunachal Pradesh' },
  { code: '13', name: 'Nagaland' },        { code: '14', name: 'Manipur' },
  { code: '15', name: 'Mizoram' },         { code: '16', name: 'Tripura' },
  { code: '17', name: 'Meghalaya' },       { code: '18', name: 'Assam' },
  { code: '19', name: 'West Bengal' },     { code: '20', name: 'Jharkhand' },
  { code: '21', name: 'Odisha' },          { code: '22', name: 'Chhattisgarh' },
  { code: '23', name: 'Madhya Pradesh' },  { code: '24', name: 'Gujarat' },
  { code: '27', name: 'Maharashtra' },     { code: '29', name: 'Karnataka' },
  { code: '30', name: 'Goa' },             { code: '32', name: 'Kerala' },
  { code: '33', name: 'Tamil Nadu' },      { code: '36', name: 'Telangana' },
  { code: '37', name: 'Andhra Pradesh' },
];

const TEMPLATES = [
  { value: 'template_classic',  label: 'Classic' },
  { value: 'template_modern',   label: 'Modern' },
  { value: 'template_compact',  label: 'Compact' },
  { value: 'template_elegant',  label: 'Elegant' },
];

const STATUS_STYLES = {
  draft:     { bg: '#f1f5f9', color: '#64748b' },
  sent:      { bg: '#eff6ff', color: '#3b82f6' },
  paid:      { bg: '#dcfce7', color: '#16a34a' },
  partial:   { bg: '#fefce8', color: '#b45309' },
  overdue:   { bg: '#fee2e2', color: '#dc2626' },
  cancelled: { bg: '#f1f5f9', color: '#94a3b8' },
};

const EMPTY_ITEM = {
  description:  '',
  hsn_sac_code: '',
  qty:          1,
  unit:         'Nos',
  unit_price:   '',
  discount_pct: 0,
  gst_rate:     18,
};

// ─── Shared styles ────────────────────────────────────────────────────────────
const inputStyle = {
  width: '100%', padding: '8px 11px',
  border: '1px solid #e2e8f0', borderRadius: 6,
  fontSize: 13, color: '#1e293b', outline: 'none',
  boxSizing: 'border-box', fontFamily: 'inherit', background: '#fff',
};

const labelStyle = {
  display: 'block', fontSize: 11, fontWeight: 600,
  color: '#64748b', marginBottom: 4, textTransform: 'uppercase', letterSpacing: '0.04em',
};

// ─── GST calculation helper ───────────────────────────────────────────────────
// If customer state == company state → CGST + SGST (intra)
// If different                       → IGST (inter)
const COMPANY_STATE = '29'; // Karnataka — change if your company is different

function calcItem(item, placeOfSupply) {
  const qty        = parseFloat(item.qty)          || 0;
  const unitPrice  = parseFloat(item.unit_price)   || 0;
  const discPct    = parseFloat(item.discount_pct) || 0;
  const gstRate    = parseFloat(item.gst_rate)     || 0;

  const grossValue   = qty * unitPrice;
  const discountAmt  = grossValue * discPct / 100;
  const taxableValue = grossValue - discountAmt;

  const isInter = placeOfSupply !== COMPANY_STATE;
  const cgst_rate  = isInter ? 0 : gstRate / 2;
  const sgst_rate  = isInter ? 0 : gstRate / 2;
  const igst_rate  = isInter ? gstRate : 0;

  const cgst_amount = taxableValue * cgst_rate / 100;
  const sgst_amount = taxableValue * sgst_rate / 100;
  const igst_amount = taxableValue * igst_rate / 100;
  const total       = taxableValue + cgst_amount + sgst_amount + igst_amount;

  return {
    ...item,
    taxable_value: taxableValue,
    cgst_rate, sgst_rate, igst_rate,
    cgst_amount, sgst_amount, igst_amount,
    cess_rate: 0, cess_amount: 0,
    total,
  };
}

function calcTotals(items, placeOfSupply) {
  const calced = items.map(it => calcItem(it, placeOfSupply));
  const subtotal     = calced.reduce((s, i) => s + i.taxable_value, 0);
  const cgst_total   = calced.reduce((s, i) => s + i.cgst_amount,   0);
  const sgst_total   = calced.reduce((s, i) => s + i.sgst_amount,   0);
  const igst_total   = calced.reduce((s, i) => s + i.igst_amount,   0);
  const total_amount = calced.reduce((s, i) => s + i.total,         0);
  return { calced, subtotal, cgst_total, sgst_total, igst_total, total_amount };
}

// ─── Small components ─────────────────────────────────────────────────────────
function StatusBadge({ status }) {
  const s = STATUS_STYLES[status] || STATUS_STYLES.draft;
  return (
    <span style={{ background: s.bg, color: s.color, padding: '3px 10px', borderRadius: 20, fontSize: 12, fontWeight: 700, textTransform: 'capitalize' }}>
      {status}
    </span>
  );
}

function SectionTitle({ children }) {
  return (
    <div style={{ fontSize: 13, fontWeight: 700, color: '#475569', textTransform: 'uppercase', letterSpacing: '0.06em', marginBottom: 12, paddingBottom: 8, borderBottom: '1px solid #f1f5f9' }}>
      {children}
    </div>
  );
}

// ─── Main Component ───────────────────────────────────────────────────────────
export default function Invoices() {
  const [view, setView]           = useState('list');   // 'list' | 'create' | 'detail'
  const [invoices, setInvoices]   = useState([]);
  const [customers, setCustomers] = useState([]);
  const [products, setProducts]   = useState([]);
  const [loading, setLoading]     = useState(true);
  const [saving, setSaving]       = useState(false);
  const [error, setError]         = useState('');
  const [success, setSuccess]     = useState('');
  const [selected, setSelected]   = useState(null);   // invoice detail view

  // ── Create form state ──
  const today    = new Date().toISOString().split('T')[0];
  const dueDate  = new Date(Date.now() + 30*24*60*60*1000).toISOString().split('T')[0];

  const [form, setForm] = useState({
    customer_id:       '',
    invoice_date:      today,
    due_date:          dueDate,
    place_of_supply:   '29',
    invoice_template:  'template_classic',
    notes:             '',
    reverse_charge:    false,
    is_export:         false,
  });
  const [items, setItems] = useState([{ ...EMPTY_ITEM }]);

  // ── Flash ──
  const flash = (msg, isErr = false) => {
    if (isErr) { setError(msg);   setTimeout(() => setError(''),   5000); }
    else        { setSuccess(msg); setTimeout(() => setSuccess(''), 3000); }
  };

  // ── Fetch all data ──
  const fetchInvoices = async () => {
    setLoading(true);
    try {
      const res = await api.get('/invoices');
      const raw = res.data;
      if (Array.isArray(raw))           setInvoices(raw);
      else if (Array.isArray(raw.data)) setInvoices(raw.data);
      else                              setInvoices([]);
    } catch (err) {
      flash(err.response?.data?.message || 'Failed to load invoices.', true);
    } finally { setLoading(false); }
  };

  const fetchCustomers = async () => {
    try {
      const res = await api.get('/customers');
      const raw = res.data;
      setCustomers(Array.isArray(raw) ? raw : raw.data || []);
    } catch {}
  };

  const fetchProducts = async () => {
    try {
      const res = await api.get('/products');
      const raw = res.data;
      setProducts(Array.isArray(raw) ? raw : raw.data || []);
    } catch {}
  };

  useEffect(() => {
    fetchInvoices();
    fetchCustomers();
    fetchProducts();
  }, []);

  // ── Totals (live calculation) ──
  const { calced, subtotal, cgst_total, sgst_total, igst_total, total_amount } =
    calcTotals(items, form.place_of_supply);

  const isInter = form.place_of_supply !== COMPANY_STATE;

  // ── Form helpers ──
  const setField = (key, val) => setForm(f => ({ ...f, [key]: val }));

  const setItem = (idx, key, val) => {
    setItems(prev => prev.map((it, i) => i === idx ? { ...it, [key]: val } : it));
  };

  const addItem = () => setItems(prev => [...prev, { ...EMPTY_ITEM }]);

  const removeItem = (idx) => {
    if (items.length === 1) return; // keep at least 1
    setItems(prev => prev.filter((_, i) => i !== idx));
  };

  // Auto-fill item when product selected
  const fillFromProduct = (idx, productId) => {
    const p = products.find(p => String(p.id) === String(productId));
    if (!p) return;
    setItems(prev => prev.map((it, i) => i === idx ? {
      ...it,
      description:  p.name,
      hsn_sac_code: p.hsn_sac_code || '',
      unit:         p.unit         || 'Nos',
      unit_price:   p.unit_price   || '',
      gst_rate:     parseInt(p.gst_rate) || 18,
    } : it));
  };

  // ── Save invoice ──
  const handleSave = async (e) => {
    e.preventDefault();
    if (!form.customer_id) { flash('Please select a customer.', true); return; }
    if (items.some(it => !it.description || !it.unit_price)) {
      flash('All line items must have a description and unit price.', true); return;
    }

    setSaving(true);
    try {
      const payload = {
        ...form,
        subtotal:    subtotal.toFixed(2),
        cgst_total:  cgst_total.toFixed(2),
        sgst_total:  sgst_total.toFixed(2),
        igst_total:  igst_total.toFixed(2),
        cess_total:  '0.00',
        total_amount: total_amount.toFixed(2),
        items: calced.map(it => ({
          description:  it.description,
          hsn_sac_code: it.hsn_sac_code,
          qty:          it.qty,
          unit:         it.unit,
          unit_price:   parseFloat(it.unit_price).toFixed(2),
          discount_pct: it.discount_pct,
          taxable_value: it.taxable_value.toFixed(2),
          gst_rate:     it.gst_rate,
          cgst_rate:    it.cgst_rate,
          sgst_rate:    it.sgst_rate,
          igst_rate:    it.igst_rate,
          cgst_amount:  it.cgst_amount.toFixed(2),
          sgst_amount:  it.sgst_amount.toFixed(2),
          igst_amount:  it.igst_amount.toFixed(2),
          cess_rate:    0,
          cess_amount:  '0.00',
          total:        it.total.toFixed(2),
        })),
      };

      await api.post('/invoices', payload);
      flash('Invoice created successfully!');
      setView('list');
      fetchInvoices();
      // Reset form
      setForm({ customer_id: '', invoice_date: today, due_date: dueDate, place_of_supply: '29', invoice_template: 'template_classic', notes: '', reverse_charge: false, is_export: false });
      setItems([{ ...EMPTY_ITEM }]);
    } catch (err) {
      const errors = err.response?.data?.errors;
      const msg = errors
        ? Object.values(errors).flat().join(' | ')
        : err.response?.data?.message || 'Failed to create invoice.';
      flash(msg, true);
    } finally { setSaving(false); }
  };

  // ── Delete invoice ──
  const handleDelete = async (inv) => {
    if (!window.confirm(`Delete invoice ${inv.invoice_number}? This cannot be undone.`)) return;
    try {
      await api.delete(`/invoices/${inv.id}`);
      flash('Invoice deleted.');
      fetchInvoices();
    } catch (err) {
      flash(err.response?.data?.message || 'Failed to delete.', true);
    }
  };

  // ── Download PDF ──
  const handlePDF = async (inv) => {
    try {
      const res = await api.get(`/invoices/${inv.id}/pdf`, { responseType: 'blob' });
      const url = window.URL.createObjectURL(new Blob([res.data], { type: 'application/pdf' }));
      const a   = document.createElement('a');
      a.href    = url;
      a.download = `${inv.invoice_number}.pdf`;
      a.click();
      window.URL.revokeObjectURL(url);
    } catch {
      flash('Failed to download PDF.', true);
    }
  };

  // ═══════════════════════════════════════════════════════════════════════════
  // RENDER
  // ═══════════════════════════════════════════════════════════════════════════
  return (
    <div style={{ minHeight: '100vh', background: '#f1f5f9', fontFamily: 'system-ui,sans-serif' }}>

      {/* Navbar */}
      <div style={{ background: '#1e293b', padding: '0 32px', height: 56, display: 'flex', alignItems: 'center', justifyContent: 'space-between', position: 'sticky', top: 0, zIndex: 100 }}>
        <span style={{ color: '#f8fafc', fontWeight: 700, fontSize: 18 }}>🧾 GST Billing System</span>
        <div style={{ display: 'flex', gap: 20, alignItems: 'center' }}>
          {view !== 'list' && (
            <button onClick={() => setView('list')} style={{ background: 'none', border: 'none', color: '#94a3b8', fontSize: 13, cursor: 'pointer' }}>
              ← Back to Invoices
            </button>
          )}
          <a href="/dashboard" style={{ color: '#94a3b8', fontSize: 13, textDecoration: 'none' }}>← Dashboard</a>
        </div>
      </div>

      <div style={{ maxWidth: 1200, margin: '0 auto', padding: '32px 24px' }}>

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

        {/* ══════════════════════════════════════════════════════════════════ */}
        {/* LIST VIEW                                                          */}
        {/* ══════════════════════════════════════════════════════════════════ */}
        {view === 'list' && (
          <>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 24 }}>
              <div>
                <h1 style={{ margin: 0, fontSize: 24, fontWeight: 700, color: '#1e293b' }}>Invoices</h1>
                <p style={{ margin: '4px 0 0', fontSize: 13, color: '#64748b' }}>{invoices.length} total invoices</p>
              </div>
              <button
                onClick={() => setView('create')}
                style={{ padding: '10px 20px', background: '#3b82f6', color: '#fff', border: 'none', borderRadius: 8, fontSize: 14, fontWeight: 600, cursor: 'pointer' }}
              >
                + Create Invoice
              </button>
            </div>

            <div style={{ background: '#fff', borderRadius: 12, boxShadow: '0 1px 4px rgba(0,0,0,0.07)', overflow: 'hidden' }}>
              {loading ? (
                <div style={{ padding: 48, textAlign: 'center', color: '#94a3b8' }}>Loading invoices…</div>
              ) : invoices.length === 0 ? (
                <div style={{ padding: 48, textAlign: 'center', color: '#94a3b8' }}>
                  No invoices yet. Click "+ Create Invoice" to start.
                </div>
              ) : (
                <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 14 }}>
                  <thead>
                    <tr style={{ background: '#1e293b' }}>
                      {['Invoice #', 'Customer', 'Date', 'Due Date', 'Amount', 'Status', 'Actions'].map(h => (
                        <th key={h} style={{ padding: '12px 16px', textAlign: 'left', color: '#cbd5e1', fontSize: 12, fontWeight: 600, whiteSpace: 'nowrap' }}>
                          {h}
                        </th>
                      ))}
                    </tr>
                  </thead>
                  <tbody>
                    {invoices.map(inv => (
                      <tr key={inv.id} style={{ borderBottom: '1px solid #f1f5f9' }}
                        onMouseEnter={e => e.currentTarget.style.background = '#f8fafc'}
                        onMouseLeave={e => e.currentTarget.style.background = 'transparent'}
                      >
                        <td style={{ padding: '12px 16px', fontWeight: 700, color: '#3b82f6', fontFamily: 'monospace', fontSize: 13 }}>
                          {inv.invoice_number}
                        </td>
                        <td style={{ padding: '12px 16px', color: '#1e293b', fontWeight: 500 }}>
                          {inv.customer?.name || `Customer #${inv.customer_id}`}
                          {inv.customer?.gstin && (
                            <div style={{ fontSize: 11, color: '#94a3b8', marginTop: 2, fontFamily: 'monospace' }}>{inv.customer.gstin}</div>
                          )}
                        </td>
                        <td style={{ padding: '12px 16px', color: '#475569' }}>{inv.invoice_date}</td>
                        <td style={{ padding: '12px 16px', color: '#475569' }}>{inv.due_date}</td>
                        <td style={{ padding: '12px 16px', fontWeight: 700, color: '#1e293b' }}>
                          ₹{parseFloat(inv.total_amount).toLocaleString('en-IN', { minimumFractionDigits: 2 })}
                        </td>
                        <td style={{ padding: '12px 16px' }}>
                          <StatusBadge status={inv.status} />
                        </td>
                        <td style={{ padding: '12px 16px' }}>
                          <div style={{ display: 'flex', gap: 6 }}>
                            <button
                              onClick={() => { setSelected(inv); setView('detail'); }}
                              style={{ padding: '4px 10px', borderRadius: 5, border: '1px solid #e2e8f0', background: '#f8fafc', color: '#475569', fontSize: 11, fontWeight: 600, cursor: 'pointer' }}
                            >
                              View
                            </button>
                            <button
                              onClick={() => handlePDF(inv)}
                              style={{ padding: '4px 10px', borderRadius: 5, border: '1px solid #7c3aed', background: '#f5f3ff', color: '#7c3aed', fontSize: 11, fontWeight: 600, cursor: 'pointer' }}
                            >
                              PDF
                            </button>
                            <button
                              onClick={() => handleDelete(inv)}
                              style={{ padding: '4px 10px', borderRadius: 5, border: '1px solid #ef4444', background: '#fef2f2', color: '#ef4444', fontSize: 11, fontWeight: 600, cursor: 'pointer' }}
                            >
                              Delete
                            </button>
                          </div>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              )}
            </div>
          </>
        )}

        {/* ══════════════════════════════════════════════════════════════════ */}
        {/* CREATE VIEW                                                        */}
        {/* ══════════════════════════════════════════════════════════════════ */}
        {view === 'create' && (
          <form onSubmit={handleSave}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 24 }}>
              <h1 style={{ margin: 0, fontSize: 22, fontWeight: 700, color: '#1e293b' }}>Create New Invoice</h1>
              <div style={{ display: 'flex', gap: 10 }}>
                <button type="button" onClick={() => setView('list')} style={{ padding: '9px 18px', background: '#fff', border: '1px solid #e2e8f0', borderRadius: 7, fontSize: 14, color: '#64748b', cursor: 'pointer' }}>
                  Cancel
                </button>
                <button type="submit" disabled={saving} style={{ padding: '9px 20px', background: saving ? '#93c5fd' : '#3b82f6', color: '#fff', border: 'none', borderRadius: 7, fontSize: 14, fontWeight: 600, cursor: saving ? 'not-allowed' : 'pointer' }}>
                  {saving ? 'Saving…' : '💾 Save Invoice'}
                </button>
              </div>
            </div>

            {/* ── Section 1: Invoice Details ── */}
            <div style={{ background: '#fff', borderRadius: 12, padding: 24, marginBottom: 16, boxShadow: '0 1px 3px rgba(0,0,0,0.06)' }}>
              <SectionTitle>Invoice Details</SectionTitle>
              <div style={{ display: 'grid', gridTemplateColumns: '2fr 1fr 1fr 1fr', gap: 16 }}>
                <div>
                  <label style={labelStyle}>Customer <span style={{ color: '#ef4444' }}>*</span></label>
                  <select
                    style={inputStyle} required
                    value={form.customer_id}
                    onChange={e => {
                      setField('customer_id', e.target.value);
                      // Auto-set place of supply from customer state
                      const cust = customers.find(c => String(c.id) === e.target.value);
                      if (cust?.state_code) setField('place_of_supply', String(cust.state_code));
                    }}
                  >
                    <option value="">Select customer…</option>
                    {customers.map(c => (
                      <option key={c.id} value={c.id}>{c.name} {c.gstin ? `(${c.gstin})` : ''}</option>
                    ))}
                  </select>
                </div>
                <div>
                  <label style={labelStyle}>Invoice Date</label>
                  <input type="date" style={inputStyle} value={form.invoice_date} onChange={e => setField('invoice_date', e.target.value)} />
                </div>
                <div>
                  <label style={labelStyle}>Due Date</label>
                  <input type="date" style={inputStyle} value={form.due_date} onChange={e => setField('due_date', e.target.value)} />
                </div>
                <div>
                  <label style={labelStyle}>Template</label>
                  <select style={inputStyle} value={form.invoice_template} onChange={e => setField('invoice_template', e.target.value)}>
                    {TEMPLATES.map(t => <option key={t.value} value={t.value}>{t.label}</option>)}
                  </select>
                </div>
              </div>

              <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr 1fr 1fr', gap: 16, marginTop: 16 }}>
                <div>
                  <label style={labelStyle}>Place of Supply</label>
                  <select style={inputStyle} value={form.place_of_supply} onChange={e => setField('place_of_supply', e.target.value)}>
                    {INDIAN_STATES.map(s => (
                      <option key={s.code} value={s.code}>{s.code} – {s.name}</option>
                    ))}
                  </select>
                </div>
                <div style={{ display: 'flex', alignItems: 'flex-end', paddingBottom: 2 }}>
                  <label style={{ display: 'flex', alignItems: 'center', gap: 8, cursor: 'pointer', fontSize: 13, color: '#475569' }}>
                    <input type="checkbox" checked={form.reverse_charge} onChange={e => setField('reverse_charge', e.target.checked)} style={{ width: 15, height: 15, accentColor: '#3b82f6' }} />
                    Reverse Charge
                  </label>
                </div>
                <div style={{ display: 'flex', alignItems: 'flex-end', paddingBottom: 2 }}>
                  <label style={{ display: 'flex', alignItems: 'center', gap: 8, cursor: 'pointer', fontSize: 13, color: '#475569' }}>
                    <input type="checkbox" checked={form.is_export} onChange={e => setField('is_export', e.target.checked)} style={{ width: 15, height: 15, accentColor: '#3b82f6' }} />
                    Export Invoice
                  </label>
                </div>
                <div>
                  {/* GST type indicator */}
                  <label style={labelStyle}>GST Type</label>
                  <div style={{ padding: '8px 12px', borderRadius: 6, background: isInter ? '#fefce8' : '#f0fdf4', color: isInter ? '#b45309' : '#166534', fontWeight: 700, fontSize: 13 }}>
                    {isInter ? '⚡ IGST (Inter-state)' : '✅ CGST + SGST (Intra-state)'}
                  </div>
                </div>
              </div>
            </div>

            {/* ── Section 2: Line Items ── */}
            <div style={{ background: '#fff', borderRadius: 12, padding: 24, marginBottom: 16, boxShadow: '0 1px 3px rgba(0,0,0,0.06)' }}>
              <SectionTitle>Line Items</SectionTitle>

              {/* Table header */}
              <div style={{ display: 'grid', gridTemplateColumns: '2fr 1fr 80px 80px 90px 70px 80px 80px 40px', gap: 8, marginBottom: 8 }}>
                {['Product / Description', 'HSN/SAC', 'Qty', 'Unit', 'Unit Price', 'Disc%', 'GST%', 'Total', ''].map(h => (
                  <div key={h} style={{ fontSize: 11, fontWeight: 700, color: '#94a3b8', textTransform: 'uppercase', letterSpacing: '0.04em' }}>{h}</div>
                ))}
              </div>

              {/* Line items */}
              {items.map((item, idx) => {
                const c = calced[idx] || calcItem(item, form.place_of_supply);
                return (
                  <div key={idx} style={{ display: 'grid', gridTemplateColumns: '2fr 1fr 80px 80px 90px 70px 80px 80px 40px', gap: 8, marginBottom: 8, alignItems: 'center' }}>

                    {/* Description + product picker */}
                    <div>
                      <select
                        style={{ ...inputStyle, marginBottom: 4, fontSize: 12, color: '#64748b' }}
                        onChange={e => fillFromProduct(idx, e.target.value)}
                        defaultValue=""
                      >
                        <option value="">Pick product…</option>
                        {products.map(p => <option key={p.id} value={p.id}>{p.name}</option>)}
                      </select>
                      <input
                        style={inputStyle}
                        value={item.description}
                        onChange={e => setItem(idx, 'description', e.target.value)}
                        placeholder="Description *"
                        required
                      />
                    </div>

                    <input style={inputStyle} value={item.hsn_sac_code} onChange={e => setItem(idx, 'hsn_sac_code', e.target.value)} placeholder="HSN" />

                    <input type="number" min="0" step="0.001" style={inputStyle} value={item.qty} onChange={e => setItem(idx, 'qty', e.target.value)} />

                    <select style={inputStyle} value={item.unit} onChange={e => setItem(idx, 'unit', e.target.value)}>
                      {['Nos','Pcs','Box','Kg','Gram','Litre','Ml','Meter','Feet','Set','Pair','Hour','Day'].map(u => (
                        <option key={u} value={u}>{u}</option>
                      ))}
                    </select>

                    <input type="number" min="0" step="0.01" style={inputStyle} value={item.unit_price} onChange={e => setItem(idx, 'unit_price', e.target.value)} placeholder="0.00" required />

                    <input type="number" min="0" max="100" step="0.01" style={inputStyle} value={item.discount_pct} onChange={e => setItem(idx, 'discount_pct', e.target.value)} />

                    <select style={inputStyle} value={item.gst_rate} onChange={e => setItem(idx, 'gst_rate', parseInt(e.target.value))}>
                      {[0, 5, 12, 18, 28].map(r => <option key={r} value={r}>{r}%</option>)}
                    </select>

                    <div style={{ fontWeight: 700, fontSize: 13, color: '#1e293b', textAlign: 'right', paddingRight: 4 }}>
                      ₹{c.total.toFixed(2)}
                    </div>

                    <button
                      type="button"
                      onClick={() => removeItem(idx)}
                      disabled={items.length === 1}
                      style={{ background: 'none', border: 'none', color: items.length === 1 ? '#e2e8f0' : '#ef4444', fontSize: 20, cursor: items.length === 1 ? 'default' : 'pointer', padding: 0, lineHeight: 1 }}
                    >×</button>
                  </div>
                );
              })}

              <button
                type="button" onClick={addItem}
                style={{ marginTop: 8, padding: '7px 16px', background: '#f0fdf4', border: '1px dashed #86efac', borderRadius: 6, color: '#16a34a', fontSize: 13, fontWeight: 600, cursor: 'pointer' }}
              >
                + Add Line Item
              </button>
            </div>

            {/* ── Section 3: Totals + Notes ── */}
            <div style={{ display: 'grid', gridTemplateColumns: '1fr 340px', gap: 16 }}>

              {/* Notes */}
              <div style={{ background: '#fff', borderRadius: 12, padding: 24, boxShadow: '0 1px 3px rgba(0,0,0,0.06)' }}>
                <SectionTitle>Notes</SectionTitle>
                <textarea
                  style={{ ...inputStyle, minHeight: 100, resize: 'vertical' }}
                  value={form.notes}
                  onChange={e => setField('notes', e.target.value)}
                  placeholder="Payment terms, bank details, thank you note…"
                />
              </div>

              {/* Tax summary */}
              <div style={{ background: '#fff', borderRadius: 12, padding: 24, boxShadow: '0 1px 3px rgba(0,0,0,0.06)' }}>
                <SectionTitle>Tax Summary</SectionTitle>

                <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 8, fontSize: 13, color: '#475569' }}>
                  <span>Subtotal (Taxable)</span>
                  <span style={{ fontWeight: 600 }}>₹{subtotal.toFixed(2)}</span>
                </div>

                {!isInter ? (
                  <>
                    <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 8, fontSize: 13, color: '#475569' }}>
                      <span>CGST</span>
                      <span>₹{cgst_total.toFixed(2)}</span>
                    </div>
                    <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 8, fontSize: 13, color: '#475569' }}>
                      <span>SGST</span>
                      <span>₹{sgst_total.toFixed(2)}</span>
                    </div>
                  </>
                ) : (
                  <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 8, fontSize: 13, color: '#475569' }}>
                    <span>IGST</span>
                    <span>₹{igst_total.toFixed(2)}</span>
                  </div>
                )}

                <div style={{ height: 1, background: '#e2e8f0', margin: '12px 0' }} />

                <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: 16, fontWeight: 800, color: '#1e293b' }}>
                  <span>Total Amount</span>
                  <span>₹{total_amount.toLocaleString('en-IN', { minimumFractionDigits: 2 })}</span>
                </div>
              </div>
            </div>

            {/* Save button at bottom */}
            <div style={{ display: 'flex', justifyContent: 'flex-end', marginTop: 20, gap: 10 }}>
              <button type="button" onClick={() => setView('list')} style={{ padding: '11px 22px', background: '#fff', border: '1px solid #e2e8f0', borderRadius: 8, fontSize: 14, color: '#64748b', cursor: 'pointer' }}>
                Cancel
              </button>
              <button type="submit" disabled={saving} style={{ padding: '11px 28px', background: saving ? '#93c5fd' : '#3b82f6', color: '#fff', border: 'none', borderRadius: 8, fontSize: 14, fontWeight: 700, cursor: saving ? 'not-allowed' : 'pointer' }}>
                {saving ? 'Saving…' : '💾 Save Invoice'}
              </button>
            </div>
          </form>
        )}

        {/* ══════════════════════════════════════════════════════════════════ */}
        {/* DETAIL VIEW                                                        */}
        {/* ══════════════════════════════════════════════════════════════════ */}
        {view === 'detail' && selected && (
          <div>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 24 }}>
              <div>
                <h1 style={{ margin: 0, fontSize: 22, fontWeight: 700, color: '#1e293b' }}>{selected.invoice_number}</h1>
                <div style={{ marginTop: 6 }}><StatusBadge status={selected.status} /></div>
              </div>
              <div style={{ display: 'flex', gap: 10 }}>
                <button onClick={() => handlePDF(selected)} style={{ padding: '9px 18px', background: '#7c3aed', color: '#fff', border: 'none', borderRadius: 7, fontSize: 13, fontWeight: 600, cursor: 'pointer' }}>
                  ⬇ Download PDF
                </button>
                <button onClick={() => setView('list')} style={{ padding: '9px 18px', background: '#fff', border: '1px solid #e2e8f0', borderRadius: 7, fontSize: 13, color: '#64748b', cursor: 'pointer' }}>
                  ← Back to List
                </button>
              </div>
            </div>

            {/* Invoice info */}
            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 16, marginBottom: 16 }}>
              <div style={{ background: '#fff', borderRadius: 12, padding: 24, boxShadow: '0 1px 3px rgba(0,0,0,0.06)' }}>
                <SectionTitle>Bill To</SectionTitle>
                <div style={{ fontWeight: 700, fontSize: 15, color: '#1e293b', marginBottom: 4 }}>{selected.customer?.name}</div>
                {selected.customer?.gstin && <div style={{ fontSize: 12, fontFamily: 'monospace', color: '#64748b' }}>GSTIN: {selected.customer.gstin}</div>}
                {selected.customer?.email && <div style={{ fontSize: 13, color: '#64748b', marginTop: 4 }}>{selected.customer.email}</div>}
                {selected.customer?.phone && <div style={{ fontSize: 13, color: '#64748b' }}>{selected.customer.phone}</div>}
                {selected.customer?.billing_address && <div style={{ fontSize: 13, color: '#64748b', marginTop: 4 }}>{selected.customer.billing_address}</div>}
              </div>

              <div style={{ background: '#fff', borderRadius: 12, padding: 24, boxShadow: '0 1px 3px rgba(0,0,0,0.06)' }}>
                <SectionTitle>Invoice Info</SectionTitle>
                {[
                  ['Invoice Date', selected.invoice_date],
                  ['Due Date',     selected.due_date],
                  ['Place of Supply', selected.place_of_supply],
                  ['Reverse Charge', selected.reverse_charge ? 'Yes' : 'No'],
                ].map(([label, val]) => (
                  <div key={label} style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 8, fontSize: 13 }}>
                    <span style={{ color: '#64748b' }}>{label}</span>
                    <span style={{ fontWeight: 600, color: '#1e293b' }}>{val}</span>
                  </div>
                ))}
              </div>
            </div>

            {/* Items */}
            <div style={{ background: '#fff', borderRadius: 12, padding: 24, marginBottom: 16, boxShadow: '0 1px 3px rgba(0,0,0,0.06)', overflow: 'hidden' }}>
              <SectionTitle>Items</SectionTitle>
              <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 13 }}>
                <thead>
                  <tr style={{ background: '#f8fafc' }}>
                    {['Description', 'HSN/SAC', 'Qty', 'Unit', 'Unit Price', 'Taxable', 'CGST', 'SGST', 'IGST', 'Total'].map(h => (
                      <th key={h} style={{ padding: '10px 12px', textAlign: 'left', fontSize: 11, fontWeight: 600, color: '#64748b', textTransform: 'uppercase', whiteSpace: 'nowrap' }}>{h}</th>
                    ))}
                  </tr>
                </thead>
                <tbody>
                  {selected.items?.map((item, i) => (
                    <tr key={i} style={{ borderTop: '1px solid #f1f5f9' }}>
                      <td style={{ padding: '10px 12px', fontWeight: 600, color: '#1e293b' }}>{item.description}</td>
                      <td style={{ padding: '10px 12px', fontFamily: 'monospace', fontSize: 12, color: '#64748b' }}>{item.hsn_sac_code || '—'}</td>
                      <td style={{ padding: '10px 12px', color: '#475569' }}>{parseFloat(item.qty)}</td>
                      <td style={{ padding: '10px 12px', color: '#475569' }}>{item.unit}</td>
                      <td style={{ padding: '10px 12px', color: '#475569' }}>₹{parseFloat(item.unit_price).toFixed(2)}</td>
                      <td style={{ padding: '10px 12px', color: '#475569' }}>₹{parseFloat(item.taxable_value).toFixed(2)}</td>
                      <td style={{ padding: '10px 12px', color: '#475569' }}>₹{parseFloat(item.cgst_amount).toFixed(2)}</td>
                      <td style={{ padding: '10px 12px', color: '#475569' }}>₹{parseFloat(item.sgst_amount).toFixed(2)}</td>
                      <td style={{ padding: '10px 12px', color: '#475569' }}>₹{parseFloat(item.igst_amount).toFixed(2)}</td>
                      <td style={{ padding: '10px 12px', fontWeight: 700, color: '#1e293b' }}>₹{parseFloat(item.total).toFixed(2)}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            {/* Totals */}
            <div style={{ display: 'flex', justifyContent: 'flex-end' }}>
              <div style={{ background: '#fff', borderRadius: 12, padding: 24, width: 320, boxShadow: '0 1px 3px rgba(0,0,0,0.06)' }}>
                {[
                  ['Subtotal',  `₹${parseFloat(selected.subtotal).toFixed(2)}`],
                  ['CGST',      `₹${parseFloat(selected.cgst_total).toFixed(2)}`],
                  ['SGST',      `₹${parseFloat(selected.sgst_total).toFixed(2)}`],
                  ['IGST',      `₹${parseFloat(selected.igst_total).toFixed(2)}`],
                ].map(([label, val]) => (
                  <div key={label} style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 8, fontSize: 13, color: '#64748b' }}>
                    <span>{label}</span><span>{val}</span>
                  </div>
                ))}
                <div style={{ height: 1, background: '#e2e8f0', margin: '12px 0' }} />
                <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: 16, fontWeight: 800, color: '#1e293b' }}>
                  <span>Total</span>
                  <span>₹{parseFloat(selected.total_amount).toLocaleString('en-IN', { minimumFractionDigits: 2 })}</span>
                </div>
              </div>
            </div>

            {selected.notes && (
              <div style={{ background: '#fff', borderRadius: 12, padding: 20, marginTop: 16, boxShadow: '0 1px 3px rgba(0,0,0,0.06)' }}>
                <SectionTitle>Notes</SectionTitle>
                <p style={{ margin: 0, fontSize: 13, color: '#64748b' }}>{selected.notes}</p>
              </div>
            )}
          </div>
        )}

      </div>
    </div>
  );
}