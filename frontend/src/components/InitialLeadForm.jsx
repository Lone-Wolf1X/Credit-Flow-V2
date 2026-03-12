import React, { useState } from 'react';
import api from '../api';
import { UserPlus, Send, Briefcase, Landmark, ChevronRight } from 'lucide-react';

const InitialLeadForm = ({ onLeadCreated, existingLead }) => {
    const [formData, setFormData] = useState({
        customer_name: existingLead?.customer_name || '',
        contact_number: existingLead?.contact_number || '',
        customer_type: existingLead?.customer_type || 'Individual',
        address: existingLead?.address || '',
        loan_type: existingLead?.loan_type || 'New',
        loan_scheme: existingLead?.loan_scheme || '',
        income_source: existingLead?.income_source || 'Business',
        proposed_limit: existingLead?.proposed_limit || '',
        is_individual: existingLead?.is_individual !== undefined ? existingLead.is_individual : true,
        is_existing_customer: existingLead?.is_existing_customer || false,
        // New Advanced Fields
        collateral_type: existingLead?.collateral_type || 'Real Estate',
        estimated_collateral_value: existingLead?.estimated_collateral_value || '',
        undivided_family_members: existingLead?.undivided_family_members || 1,
        is_pep: existingLead?.is_pep || false,
        has_legal_dispute: existingLead?.has_legal_dispute || false,
        primary_income: existingLead?.primary_income || '',
        secondary_income: existingLead?.secondary_income || '',
        other_income_amount: existingLead?.other_income_amount || '',
        other_income_source: existingLead?.other_income_source || ''
    });

    const [activeSection, setActiveSection] = useState('Basic'); // Basic, Collateral, Income

    const handleChange = (e) => {
        const { name, value, type, checked } = e.target;
        setFormData({ 
            ...formData, 
            [name]: type === 'checkbox' ? checked : value 
        });
    };

    const handleSubmit = async (e) => {
        if (e) e.preventDefault();
        try {
            await api.post('/leads', formData);
            alert('Lead generated successfully with Advanced Phase 1 Scoring!');
            onLeadCreated();
        } catch (err) {
            console.error(err);
            alert('Error processing lead. Please check the console.');
        }
    };

    const SectionTab = ({ id, label, icon: Icon }) => (
        <button 
            type="button" 
            onClick={() => setActiveSection(id)}
            style={{ 
                flex: 1, padding: '12px', border: 'none', background: activeSection === id ? 'var(--primary)' : 'transparent',
                color: activeSection === id ? 'white' : 'var(--text-muted)', fontWeight: '700', borderRadius: '10px',
                display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '8px', cursor: 'pointer', transition: 'all 0.2s'
            }}
        >
            <Icon size={18} /> {label}
        </button>
    );

    return (
        <div className="glass-card" style={{ marginBottom: '30px', borderTop: '4px solid var(--primary)', padding: '0' }}>
            <div style={{ padding: '24px', borderBottom: '1px solid var(--glass-border)', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                <h3 style={{ margin: 0, display: 'flex', alignItems: 'center', gap: '12px', fontWeight: '800' }}>
                    <UserPlus size={26} color="var(--primary)" />
                    Phase 1: Advanced Lead Qualification
                </h3>
                <div style={{ display: 'flex', gap: '5px', background: 'var(--bg-main)', padding: '5px', borderRadius: '12px', width: '400px' }}>
                    <SectionTab id="Basic" label="Basic" icon={UserPlus} />
                    <SectionTab id="Collateral" label="Asset & Risk" icon={Landmark} />
                    <SectionTab id="Income" label="Financials" icon={Briefcase} />
                </div>
            </div>

            <form onSubmit={handleSubmit} style={{ padding: '30px' }}>
                {activeSection === 'Basic' && (
                    <div className="section-fade">
                        <div className="form-row">
                            <div className="form-group">
                                <label>Lead Category</label>
                                <div style={{ display: 'flex', gap: '10px' }}>
                                    <button 
                                        type="button" 
                                        className="btn" 
                                        style={{ 
                                            flex: 1, 
                                            background: formData.is_individual ? 'var(--primary-light)' : 'white',
                                            color: formData.is_individual ? 'var(--primary)' : 'var(--text-main)',
                                            border: formData.is_individual ? '1px solid var(--primary)' : '1px solid var(--glass-border)',
                                            padding: '10px', fontWeight: '700'
                                        }}
                                        onClick={() => setFormData({...formData, is_individual: true, customer_type: 'Individual'})}
                                    >
                                        Individual
                                    </button>
                                    <button 
                                        type="button" 
                                        className="btn" 
                                        style={{ 
                                            flex: 1, 
                                            background: !formData.is_individual ? 'var(--primary-light)' : 'white',
                                            color: !formData.is_individual ? 'var(--primary)' : 'var(--text-main)',
                                            border: !formData.is_individual ? '1px solid var(--primary)' : '1px solid var(--glass-border)',
                                            padding: '10px', fontWeight: '700'
                                        }}
                                        onClick={() => setFormData({...formData, is_individual: false, customer_type: 'Business'})}
                                    >
                                        Institutional
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div className="form-row" style={{ marginTop: '20px' }}>
                            <div className="form-group">
                                <label>{formData.is_individual ? 'Customer Name' : 'Entity Name'}</label>
                                <input name="customer_name" value={formData.customer_name} onChange={handleChange} placeholder="Required" required />
                            </div>
                            <div className="form-group">
                                <label>Contact Number</label>
                                <input name="contact_number" value={formData.contact_number} onChange={handleChange} placeholder="Required" required />
                            </div>
                        </div>

                        <div className="form-row">
                            <div className="form-group">
                                <label>Address</label>
                                <input name="address" value={formData.address} onChange={handleChange} required />
                            </div>
                            <div className="form-group">
                                <label>Loan Scheme</label>
                                <input name="loan_scheme" value={formData.loan_scheme} onChange={handleChange} placeholder="e.g. SME Working Capital" />
                            </div>
                        </div>
                    </div>
                )}

                {activeSection === 'Collateral' && (
                    <div className="section-fade">
                        <div className="form-row">
                            <div className="form-group">
                                <label>Proposed Collateral Type</label>
                                <select name="collateral_type" value={formData.collateral_type} onChange={handleChange}>
                                    <option value="Real Estate">Real Estate (Land/Building)</option>
                                    <option value="Vehicle">Vehicle / Machinery</option>
                                    <option value="Gold/Silver">Gold / Silver</option>
                                    <option value="FD/Bonds">Fixed Deposit / Bonds</option>
                                    <option value="Stock">Stock Portfolio</option>
                                    <option value="Unsecured">Personal Guarantee (Unsecured)</option>
                                </select>
                            </div>
                            <div className="form-group">
                                <label>Estimated Market Value (रु)</label>
                                <input type="number" name="estimated_collateral_value" value={formData.estimated_collateral_value} onChange={handleChange} required />
                            </div>
                        </div>

                        <div className="form-row">
                            <div className="form-group">
                                <label>Undivided Family Members</label>
                                <input type="number" name="undivided_family_members" value={formData.undivided_family_members} onChange={handleChange} min="1" required />
                            </div>
                            <div className="form-group" style={{ display: 'flex', flexWrap: 'wrap', gap: '20px', alignItems: 'center', paddingTop: '10px' }}>
                                <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                                    <input type="checkbox" name="is_pep" checked={formData.is_pep} onChange={handleChange} style={{ width: '18px', height: '18px' }} />
                                    <label style={{ margin: 0 }}>PEP (Politically Exposed)</label>
                                </div>
                                <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                                    <input type="checkbox" name="has_legal_dispute" checked={formData.has_legal_dispute} onChange={handleChange} style={{ width: '18px', height: '18px' }} />
                                    <label style={{ margin: 0 }}>Active Legal Disputes?</label>
                                </div>
                            </div>
                        </div>
                    </div>
                )}

                {activeSection === 'Income' && (
                    <div className="section-fade">
                        <div className="form-row">
                            <div className="form-group">
                                <label>Primary Income Source</label>
                                <select name="income_source" value={formData.income_source} onChange={handleChange}>
                                    <option value="Government">Government Service</option>
                                    <option value="Private">Private Sector</option>
                                    <option value="Business">Trading/Business</option>
                                    <option value="Agriculture">Agriculture</option>
                                    <option value="Foreign">Foreign Employment</option>
                                    <option value="Self-Employed">Self-Employed Professional</option>
                                </select>
                            </div>
                            <div className="form-group">
                                <label>Primary Monthly Income (रु)</label>
                                <input type="number" name="primary_income" value={formData.primary_income} onChange={handleChange} required />
                            </div>
                        </div>

                        <div className="form-row">
                            <div className="form-group">
                                <label>Secondary Monthly Income (रु)</label>
                                <input type="number" name="secondary_income" value={formData.secondary_income} onChange={handleChange} />
                            </div>
                            <div className="form-group">
                                <label>Other Income Source</label>
                                <input name="other_income_source" value={formData.other_income_source} onChange={handleChange} placeholder="e.g. Rent, Interest" />
                            </div>
                            <div className="form-group">
                                <label>Other Monthly Amount</label>
                                <input type="number" name="other_income_amount" value={formData.other_income_amount} onChange={handleChange} />
                            </div>
                        </div>

                        <div className="form-row">
                            <div className="form-group">
                                <label>Proposed Limit (रु)</label>
                                <input type="number" name="proposed_limit" value={formData.proposed_limit} onChange={handleChange} required />
                            </div>
                        </div>
                    </div>
                )}

                <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '15px', marginTop: '40px', borderTop: '1px solid var(--glass-border)', paddingTop: '20px' }}>
                    {activeSection !== 'Income' ? (
                        <button 
                            type="button" 
                            className="btn btn-secondary" 
                            style={{ padding: '12px 30px' }}
                            onClick={() => setActiveSection(activeSection === 'Basic' ? 'Collateral' : 'Income')}
                        >
                            Next Step <ChevronRight size={18} />
                        </button>
                    ) : (
                        <button 
                            type="submit" 
                            className="btn btn-primary" 
                            style={{ padding: '14px 50px', fontWeight: '800' }}
                        >
                            Generate Qualified Lead <Send size={20} />
                        </button>
                    )}
                </div>
            </form>
        </div>
    );
};

export default InitialLeadForm;
