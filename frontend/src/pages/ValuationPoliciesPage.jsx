import React, { useState, useEffect } from 'react';
import api from '../api';
import { Layers, Plus, Save, Trash2, Percent, IndianRupee, Settings, Edit } from 'lucide-react';
import toast from 'react-hot-toast';

const ValuationPoliciesPage = () => {
    const [policies, setPolicies] = useState([]);
    const [paymentRules, setPaymentRules] = useState([]);
    const [activeTab, setActiveTab] = useState('Financing');
    const [newPolicy, setNewPolicy] = useState({ loan_segment: 'Retail', collateral_type: 'Real Estate', max_financing_percentage: '' });
    const [newRule, setNewRule] = useState({ rule_type: 'Fixed', min_loan_amount: '', max_loan_amount: '', field_charge: '', final_charge: '' });

    useEffect(() => {
        fetchData();
    }, []);

    const handleEditPolicy = (p) => {
        setNewPolicy({
            loan_segment: p.loan_segment,
            collateral_type: p.collateral_type,
            max_financing_percentage: p.max_financing_percentage
        });
        // Feedback to user that form is pre-filled
        toast.info(`Editing policy for ${p.loan_segment} - ${p.collateral_type}`);
    };

    const fetchData = async () => {
        try {
            const [polRes, payRes] = await Promise.all([
                api.get('/policies/valuation'),
                api.get('/policies/payments')
            ]);
            setPolicies(polRes.data);
            setPaymentRules(payRes.data);
        } catch (err) {
            toast.error('Failed to load policies');
        }
    };

    const handleSavePolicy = async (e) => {
        e.preventDefault();
        try {
            await api.post('/policies/valuation', newPolicy);
            toast.success('Policy updated successfully');
            fetchData();
        } catch (err) {
            toast.error('Failed to save policy');
        }
    };

    const handleSaveRule = async (e) => {
        e.preventDefault();
        try {
            await api.post('/policies/payments', newRule);
            toast.success('Payment rule added');
            fetchData();
        } catch (err) {
            toast.error('Failed to save payment rule');
        }
    };

    return (
        <div style={{ padding: '20px' }}>
            <div style={{ marginBottom: '30px' }}>
                <h1 style={{ fontSize: '1.8rem', fontWeight: '800', margin: 0, display: 'flex', alignItems: 'center', gap: '15px' }}>
                    <Layers size={32} color="var(--primary)" />
                    Valuation & Financing Policies
                </h1>
                <p style={{ color: 'var(--text-muted)', margin: '5px 0 0 0' }}>Configure global LTV (Loan-to-Value) and Valuation Fee structures</p>
            </div>

            <div className="tabs-container" style={{ marginBottom: '25px' }}>
                <button 
                    onClick={() => setActiveTab('Financing')}
                    className={`tab-btn ${activeTab === 'Financing' ? 'active' : ''}`}
                >
                    Financing Limits (LTV)
                </button>
                <button 
                    onClick={() => setActiveTab('Payments')}
                    className={`tab-btn ${activeTab === 'Payments' ? 'active' : ''}`}
                >
                    Valuation Charges
                </button>
            </div>

            {activeTab === 'Financing' && (
                <div style={{ display: 'grid', gridTemplateColumns: '1fr 350px', gap: '25px' }}>
                    <div className="glass-card" style={{ padding: '25px' }}>
                        <h4 style={{ marginTop: 0, marginBottom: '20px', fontWeight: '800' }}>Active Financing Matrix</h4>
                        <div className="table-container">
                            <table className="data-table">
                                <thead>
                                    <tr>
                                        <th>Loan Segment</th>
                                        <th>Collateral Type</th>
                                        <th>Max Financing (%)</th>
                                        <th>Updated At</th>
                                        <th style={{ textAlign: 'right' }}>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {policies.map(p => (
                                        <tr key={p.id}>
                                            <td style={{ fontWeight: '700' }}>{p.loan_segment}</td>
                                            <td>{p.collateral_type}</td>
                                            <td style={{ color: 'var(--primary)', fontWeight: '800' }}>{p.max_financing_percentage}%</td>
                                            <td>{new Date(p.updated_at).toLocaleDateString()}</td>
                                            <td style={{ textAlign: 'right' }}>
                                                <button 
                                                    onClick={() => handleEditPolicy(p)} 
                                                    className="action-btn"
                                                    title="Edit Policy"
                                                >
                                                    <Edit size={16} />
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div className="glass-card" style={{ padding: '25px', borderTop: '4px solid var(--primary)' }}>
                        <h4 style={{ marginTop: 0, marginBottom: '20px', fontWeight: '800' }}>Add/Update Policy</h4>
                        <form onSubmit={handleSavePolicy}>
                            <div className="form-group">
                                <label>Loan Segment</label>
                                <select className="form-input" value={newPolicy.loan_segment} onChange={e => setNewPolicy({...newPolicy, loan_segment: e.target.value})}>
                                    <option value="Retail">Retail</option>
                                    <option value="Corporate">Corporate</option>
                                    <option value="SME/MSME">SME/MSME</option>
                                    <option value="Agriculture">Agriculture</option>
                                </select>
                            </div>
                            <div className="form-group">
                                <label>Collateral Type</label>
                                <select className="form-input" value={newPolicy.collateral_type} onChange={e => setNewPolicy({...newPolicy, collateral_type: e.target.value})}>
                                    <option value="Real Estate">Real Estate</option>
                                    <option value="Vehicle">Vehicle</option>
                                    <option value="Gold/Silver">Gold/Silver</option>
                                    <option value="FD/Bonds">FD/Bonds</option>
                                    <option value="Stock">Stock</option>
                                </select>
                            </div>
                            <div className="form-group">
                                <label>Max Financing (%)</label>
                                <div style={{ position: 'relative' }}>
                                    <input type="number" step="0.1" className="form-input" value={newPolicy.max_financing_percentage} onChange={e => setNewPolicy({...newPolicy, max_financing_percentage: e.target.value})} required />
                                    <Percent size={16} style={{ position: 'absolute', right: '15px', top: '50%', transform: 'translateY(-50%)', color: 'var(--text-muted)' }} />
                                </div>
                            </div>
                            <button type="submit" className="btn btn-primary" style={{ width: '100%', marginTop: '10px', display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '8px' }}>
                                <Save size={18} /> Update Matrix
                            </button>
                        </form>
                    </div>
                </div>
            )}

            {activeTab === 'Payments' && (
                <div className="glass-card" style={{ padding: '25px' }}>
                    <h4 style={{ marginTop: 0, marginBottom: '20px', fontWeight: '800' }}>Valuation Payment Slab Config</h4>
                    <div className="table-container" style={{ marginBottom: '30px' }}>
                        <table className="data-table">
                            <thead>
                                <tr>
                                    <th>Loan Slab (रु)</th>
                                    <th>Field Charge (रु)</th>
                                    <th>Final Report Charge</th>
                                    <th>Type</th>
                                </tr>
                            </thead>
                            <tbody>
                                {paymentRules.map(r => (
                                    <tr key={r.id}>
                                        <td>{r.min_loan_amount} - {r.max_loan_amount || '∞'}</td>
                                        <td style={{ color: '#166534', fontWeight: '600' }}>रु {r.field_charge}</td>
                                        <td className="text-primary font-bold">
                                            {r.rule_type === 'Percentage' ? `${r.final_charge}% of Loan` : `रु ${r.final_charge}`}
                                        </td>
                                        <td>
                                            <span className={`badge ${r.rule_type === 'Fixed' ? 'badge-neutral' : 'badge-primary'}`}>
                                                {r.rule_type}
                                            </span>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    <div style={{ background: '#f8fafc', padding: '20px', borderRadius: '12px', border: '1px dashed #cbd5e1' }}>
                        <h5 style={{ margin: '0 0 15px 0', display: 'flex', alignItems: 'center', gap: '10px' }}><Settings size={18} /> Add New Pricing Slab</h5>
                        <form onSubmit={handleSaveRule} style={{ display: 'grid', gridTemplateColumns: '1fr 1fr 1fr 1fr 1fr 150px', gap: '15px', alignItems: 'flex-end' }}>
                            <div className="form-group" style={{ margin: 0 }}>
                                <label>Rule Type</label>
                                <select className="form-input" value={newRule.rule_type} onChange={e => setNewRule({...newRule, rule_type: e.target.value})}>
                                    <option value="Fixed">Fixed Amount</option>
                                    <option value="Percentage">Percentage (%)</option>
                                </select>
                            </div>
                            <div className="form-group" style={{ margin: 0 }}>
                                <label>Min Loan (रु)</label>
                                <input type="number" className="form-input" value={newRule.min_loan_amount} onChange={e => setNewRule({...newRule, min_loan_amount: e.target.value})} required />
                            </div>
                            <div className="form-group" style={{ margin: 0 }}>
                                <label>Max Loan (रु)</label>
                                <input type="number" className="form-input" value={newRule.max_loan_amount} onChange={e => setNewRule({...newRule, max_loan_amount: e.target.value})} placeholder="Infinite if blank" />
                            </div>
                            <div className="form-group" style={{ margin: 0 }}>
                                <label>Field Fee (रु)</label>
                                <input type="number" className="form-input" value={newRule.field_charge} onChange={e => setNewRule({...newRule, field_charge: e.target.value})} required />
                            </div>
                            <div className="form-group" style={{ margin: 0 }}>
                                <label>Final Fee</label>
                                <input type="number" className="form-input" value={newRule.final_charge} onChange={e => setNewRule({...newRule, final_charge: e.target.value})} required />
                            </div>
                            <button type="submit" className="btn btn-primary" style={{ padding: '12px' }}>
                                <Plus size={20} /> Add Slab
                            </button>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
};

export default ValuationPoliciesPage;
