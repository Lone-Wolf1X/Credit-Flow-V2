import React, { useState } from 'react';
import api from '../api';
import { UserPlus, Send, Briefcase, Landmark, ChevronRight } from 'lucide-react';

const PRODUCT_MAPPING = {
    'Retail': [
        { label: 'Personal Term Loan - PML', value: 'Personal Term Loan' },
        { label: 'Mortgage Plus Overdraft Loan - MPOD', value: 'Mortgage Plus Overdraft Loan' },
        { label: 'Hire Purchase Loan - HPL', value: 'Hire Purchase Loan' },
        { label: 'Housing Loan - HSL', value: 'Housing Loan' },
        { label: 'Professional Loan - PROFL', value: 'Professional Loan' },
        { label: 'Loan Against FD Receipt - LAFDR', value: 'Loan Against Fixed Deposit Receipt' }
    ],
    'Corporate': [
        { label: 'Working Term Loan - WTL', value: 'Working Term Loan' },
        { label: 'Mortgage Term Loan - MTL', value: 'Mortgage Term Loan' },
        { label: 'Deprived Sector Lending - DSL', value: 'Deprived Sector Lending' },
        { label: 'Micro Loan - MCOR', value: 'Micro Loan' }
    ],
    'SME/MSME': [
        { label: 'SME Business Loan', value: 'SME Business Loan' },
        { label: 'MSME Loan', value: 'MSME Loan' }
    ]
};

const InitialLeadForm = ({ onLeadCreated, existingLead }) => {
    const [formData, setFormData] = useState({
        customer_name: existingLead?.customer_name || '',
        contact_number: existingLead?.contact_number || '',
        customer_type: existingLead?.customer_type || 'Retail',
        address: existingLead?.address || '',
        loan_type: existingLead?.loan_type || '',
        loan_scheme: existingLead?.loan_scheme || '',
        income_source: existingLead?.income_source || 'Business',
        proposed_limit: existingLead?.proposed_limit || '',
        is_individual: existingLead?.is_individual !== undefined ? existingLead.is_individual : true,
        is_existing_customer: existingLead?.is_existing_customer || false,
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

    const [activeSection, setActiveSection] = useState('Basic');
    const [isDirectAppraisal, setIsDirectAppraisal] = useState(false);

    const handleChange = (e) => {
        const { name, value, type, checked } = e.target;
        setFormData(prev => ({ 
            ...prev, 
            [name]: type === 'checkbox' ? checked : value,
            ...(name === 'customer_type' ? { loan_type: '' } : {})
        }));
    };

    const handleSubmit = async (e) => {
        if (e) e.preventDefault();
        try {
            await api.post('/leads', {
                ...formData,
                loan_segment: formData.customer_type,
                loan_scheme: formData.loan_type,
                is_direct_appraisal: isDirectAppraisal
            });
            alert(isDirectAppraisal ? 'Direct Appraisal created successfully!' : 'Lead generated successfully!');
            onLeadCreated();
        } catch (err) {
            console.error(err);
            alert('Error processing lead.');
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

            <div style={{ padding: '15px 30px', background: isDirectAppraisal ? '#fef2f2' : '#f8fafc', borderBottom: '1px solid var(--glass-border)', display: 'flex', alignItems: 'center', gap: '15px' }}>
                <input 
                    type="checkbox" 
                    id="is_direct_appraisal"
                    checked={isDirectAppraisal}
                    onChange={(e) => setIsDirectAppraisal(e.target.checked)}
                    style={{ width: '20px', height: '20px', accentColor: '#dc2626' }}
                />
                <div>
                    <label htmlFor="is_direct_appraisal" style={{ margin: 0, fontWeight: '800', color: isDirectAppraisal ? '#dc2626' : 'var(--primary)', cursor: 'pointer', display: 'block' }}>
                        Existing Bank File (Direct Appraisal Bypass)
                    </label>
                    <small style={{ color: 'var(--text-muted)' }}>Skip lead qualification phases and jump straight to Appraisal Maker. Only use for existing files.</small>
                </div>
            </div>

            <form onSubmit={handleSubmit} style={{ padding: '30px' }}>
                {activeSection === 'Basic' && (
                    <div className="section-fade">
                        <div className="grid-3">
                            <div className="form-group">
                                <label>Customer Type (Loan Segment)</label>
                                <select 
                                    name="customer_type" 
                                    value={formData.customer_type} 
                                    onChange={handleChange}
                                    style={{ width: '100%', padding: '10px', borderRadius: '8px', border: '1px solid var(--glass-border)', fontWeight: '700' }}
                                >
                                    {Object.keys(PRODUCT_MAPPING).map(segment => (
                                        <option key={segment} value={segment}>{segment}</option>
                                    ))}
                                </select>
                            </div>
                            <div className="form-group">
                                <label>Loan Product (Type)</label>
                                <select 
                                    name="loan_type" 
                                    value={formData.loan_type} 
                                    onChange={handleChange}
                                    required
                                    style={{ width: '100%', padding: '10px', borderRadius: '8px', border: '1px solid var(--glass-border)', fontWeight: '700' }}
                                >
                                    <option value="">Select Product...</option>
                                    {(PRODUCT_MAPPING[formData.customer_type] || []).map(product => (
                                        <option key={product.value} value={product.value}>{product.label}</option>
                                    ))}
                                </select>
                            </div>
                            <div className="form-group">
                                <label>Individual or Organization?</label>
                                <div style={{ display: 'flex', gap: '5px' }}>
                                    <button 
                                        type="button" 
                                        className="btn btn-sm" 
                                        style={{ 
                                            flex: 1, 
                                            background: formData.is_individual ? 'var(--primary-light)' : 'white',
                                            color: formData.is_individual ? 'var(--primary)' : 'var(--text-main)',
                                            border: formData.is_individual ? '1px solid var(--primary)' : '1px solid var(--glass-border)',
                                            padding: '8px', fontWeight: '700', fontSize: '0.75rem'
                                        }}
                                        onClick={() => setFormData({...formData, is_individual: true})}
                                    >
                                        Individual
                                    </button>
                                    <button 
                                        type="button" 
                                        className="btn btn-sm" 
                                        style={{ 
                                            flex: 1, 
                                            background: !formData.is_individual ? 'var(--primary-light)' : 'white',
                                            color: !formData.is_individual ? 'var(--primary)' : 'var(--text-main)',
                                            border: !formData.is_individual ? '1px solid var(--primary)' : '1px solid var(--glass-border)',
                                            padding: '8px', fontWeight: '700', fontSize: '0.75rem'
                                        }}
                                        onClick={() => setFormData({...formData, is_individual: false})}
                                    >
                                        Institutional
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div className="grid-3" style={{ marginTop: '20px' }}>
                            <div className="form-group">
                                <label>Customer / Entity Name</label>
                                <input name="customer_name" value={formData.customer_name} onChange={handleChange} placeholder="Required" required />
                            </div>
                            <div className="form-group">
                                <label>Contact Number</label>
                                <input name="contact_number" value={formData.contact_number} onChange={handleChange} placeholder="Required" required />
                            </div>
                            <div className="form-group">
                                <label>Address</label>
                                <input name="address" value={formData.address} onChange={handleChange} required />
                            </div>
                        </div>

                        {isDirectAppraisal && (
                            <div className="form-row" style={{ marginTop: '20px', background: '#fef2f2', padding: '15px', borderRadius: '8px', border: '1px dashed #fecaca' }}>
                                <div className="form-group" style={{ flex: 1, margin: 0 }}>
                                    <label style={{ color: '#991b1b' }}>Proposed Limit (रु) - Required for Workflow</label>
                                    <input type="number" name="proposed_limit" value={formData.proposed_limit} onChange={handleChange} required />
                                </div>
                                <div style={{ flex: 1 }}></div>
                            </div>
                        )}
                    </div>
                )}

                {activeSection === 'Collateral' && (
                    <div className="section-fade">
                        <div className="grid-3">
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
                                <input type="number" name="estimated_collateral_value" value={formData.estimated_collateral_value} onChange={handleChange} required={!isDirectAppraisal} />
                            </div>
                            <div className="form-group">
                                <label>Undivided Family Members</label>
                                <input type="number" name="undivided_family_members" value={formData.undivided_family_members} onChange={handleChange} min="1" required={!isDirectAppraisal} />
                            </div>
                        </div>

                        <div className="grid-3" style={{ marginTop: '20px' }}>
                            <div className="form-group" style={{ display: 'flex', flexDirection: 'column', gap: '8px', justifyContent: 'center' }}>
                                <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                                    <input type="checkbox" name="is_pep" checked={formData.is_pep} onChange={handleChange} style={{ width: '20px', height: '20px' }} />
                                    <label style={{ margin: 0, fontWeight: '700' }}>PEP (Politically Exposed)</label>
                                </div>
                            </div>
                            <div className="form-group" style={{ display: 'flex', flexDirection: 'column', gap: '8px', justifyContent: 'center' }}>
                                <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                                    <input type="checkbox" name="has_legal_dispute" checked={formData.has_legal_dispute} onChange={handleChange} style={{ width: '20px', height: '20px' }} />
                                    <label style={{ margin: 0, fontWeight: '700' }}>Active Legal Disputes?</label>
                                </div>
                            </div>
                        </div>
                    </div>
                )}

                {activeSection === 'Income' && (
                    <div className="section-fade">
                        <div className="grid-3">
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
                                <input type="number" name="primary_income" value={formData.primary_income} onChange={handleChange} required={!isDirectAppraisal} />
                            </div>
                            <div className="form-group">
                                <label>Secondary Monthly Income (रु)</label>
                                <input type="number" name="secondary_income" value={formData.secondary_income} onChange={handleChange} />
                            </div>
                        </div>

                        <div className="grid-3" style={{ marginTop: '20px' }}>
                            <div className="form-group">
                                <label>Other Income Source</label>
                                <input name="other_income_source" value={formData.other_income_source} onChange={handleChange} placeholder="e.g. Rent, Interest" />
                            </div>
                            <div className="form-group">
                                <label>Other Monthly Amount</label>
                                <input type="number" name="other_income_amount" value={formData.other_income_amount} onChange={handleChange} />
                            </div>
                            <div className="form-group">
                                <label>Proposed Limit (रु)</label>
                                <input type="number" name="proposed_limit" value={formData.proposed_limit} onChange={handleChange} required={!isDirectAppraisal} />
                            </div>
                        </div>
                    </div>
                )}

                <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '15px', marginTop: '40px', borderTop: '1px solid var(--glass-border)', paddingTop: '20px' }}>
                    {isDirectAppraisal ? (
                        <button 
                            type="submit" 
                            className="btn btn-danger" 
                            style={{ padding: '14px 50px', fontWeight: '800', background: '#dc2626', color: 'white' }}
                        >
                            Create Direct Appraisal <Send size={20} />
                        </button>
                    ) : activeSection !== 'Income' ? (
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
