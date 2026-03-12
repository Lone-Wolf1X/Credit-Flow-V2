import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import api from '../api';
import { 
    FileText, Calculator, ShieldCheck, MapPin, 
    ArrowLeft, Save, CheckCircle, TrendingUp,
    Briefcase, Users, AlertTriangle
} from 'lucide-react';
import { motion } from 'framer-motion';

const AppraisalPage = () => {
    const { id } = useParams();
    const navigate = useNavigate();
    const [lead, setLead] = useState(null);
    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    const [activeTab, setActiveTab] = useState('Income');

    const [appraisalData, setAppraisalData] = useState({
        // Financials
        monthly_income: 0,
        monthly_expenses: 0,
        emi_outflow: 0,
        other_BFIs_exposure: 0,
        
        // Collateral
        fair_market_value: 0,
        distress_value: 0,
        collateral_location: '',
        is_mortgageable: true,
        
        // Risk
        cra_score: 70,
        mitigating_factors: '',
        unit_inspection_notes: '',
        
        // Final Proposal
        recommended_limit: 0,
        interest_rate: 12.5,
        tenure_months: 60
    });

    useEffect(() => {
        fetchLead();
    }, [id]);

    const fetchLead = async () => {
        try {
            const res = await api.get(`/leads/${id}/details`);
            setLead(res.data);
            setAppraisalData(prev => ({
                ...prev,
                recommended_limit: res.data.proposed_limit,
                monthly_income: res.data.verified_income || res.data.primary_income
            }));
            setLoading(false);
        } catch (err) {
            console.error('Error fetching lead');
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setSubmitting(true);
        try {
            // Placeholder for appraisal submission API
            await api.post(`/appraisals/${id}`, appraisalData);
            alert('Appraisal Submitted! Onboarding Report Generated.');
            navigate(`/leads/${id}`);
        } catch (err) {
            alert('Error submitting appraisal');
        } finally {
            setSubmitting(false);
        }
    };

    if (loading) return <div className="p-20 text-center">Loading Appraisal Registry...</div>;

    return (
        <div className="container" style={{ padding: '20px' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '30px' }}>
                <div>
                    <button onClick={() => navigate(-1)} className="btn btn-secondary" style={{ marginBottom: '15px' }}>
                        <ArrowLeft size={18} /> Back
                    </button>
                    <h1 style={{ fontWeight: '900', margin: 0 }}>Formal Appraisal Module</h1>
                    <p style={{ color: 'var(--text-muted)' }}>Customer: <strong>{lead.customer_name}</strong> | ID: {lead.lead_id}</p>
                </div>
                <div style={{ textAlign: 'right' }}>
                    <div className="glass-card" style={{ padding: '10px 20px', background: 'var(--primary)', color: 'white' }}>
                        <small style={{ opacity: 0.8 }}>Proposed Limit</small>
                        <div style={{ fontSize: '1.2rem', fontWeight: '900' }}>रु {parseFloat(lead.proposed_limit).toLocaleString()}</div>
                    </div>
                </div>
            </div>

            <div style={{ display: 'grid', gridTemplateColumns: '250px 1fr', gap: '24px' }}>
                {/* Tabs */}
                <div style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
                    {['Income', 'Collateral', 'Risk', 'Final Recommendation'].map(tab => (
                        <button 
                            key={tab}
                            onClick={() => setActiveTab(tab)}
                            style={{ 
                                padding: '15px 20px', textAlign: 'left', borderRadius: '12px', border: 'none',
                                background: activeTab === tab ? 'var(--primary)' : 'white',
                                color: activeTab === tab ? 'white' : 'var(--text-main)',
                                fontWeight: '700', cursor: 'pointer', transition: '0.3s'
                            }}
                        >
                            {tab}
                        </button>
                    ))}
                </div>

                {/* Content */}
                <form onSubmit={handleSubmit} className="glass-card" style={{ padding: '40px' }}>
                    {activeTab === 'Income' && (
                        <motion.div initial={{ opacity: 0 }} animate={{ opacity: 1 }}>
                            <h2 style={{ marginBottom: '25px', display: 'flex', alignItems: 'center', gap: '10px' }}>
                                <Briefcase className="text-primary" /> Financial Appraisal & DTI
                            </h2>
                            <div className="form-row">
                                <div className="form-group">
                                    <label>Total Verified Monthly Income (रु)</label>
                                    <input 
                                        type="number" value={appraisalData.monthly_income}
                                        onChange={(e) => setAppraisalData({...appraisalData, monthly_income: e.target.value})}
                                    />
                                </div>
                                <div className="form-group">
                                    <label>Family Monthly Expenses (रु)</label>
                                    <input 
                                        type="number" value={appraisalData.monthly_expenses}
                                        onChange={(e) => setAppraisalData({...appraisalData, monthly_expenses: e.target.value})}
                                    />
                                </div>
                            </div>
                            <div className="form-row">
                                <div className="form-group">
                                    <label>Existing EMI/Outflow (रु)</label>
                                    <input 
                                        type="number" value={appraisalData.emi_outflow}
                                        onChange={(e) => setAppraisalData({...appraisalData, emi_outflow: e.target.value})}
                                    />
                                </div>
                                <div className="form-group">
                                    <label>Other BFI Exposure (Total Debt)</label>
                                    <input 
                                        type="number" value={appraisalData.other_BFIs_exposure}
                                        onChange={(e) => setAppraisalData({...appraisalData, other_BFIs_exposure: e.target.value})}
                                    />
                                </div>
                            </div>
                            <div style={{ marginTop: '20px', padding: '20px', background: '#f8fafc', borderRadius: '12px', border: '1px dashed #cbd5e1' }}>
                                <strong>DTI Calculation:</strong> {( ( (parseFloat(appraisalData.emi_outflow) || 0) / (parseFloat(appraisalData.monthly_income) || 1) ) * 100).toFixed(2)}%
                                <p style={{ fontSize: '0.8rem', color: 'var(--text-muted)', marginTop: '5px' }}>Debt-to-Income ratio should ideally be below 50% for standard Retail Loans.</p>
                            </div>
                        </motion.div>
                    )}

                    {activeTab === 'Collateral' && (
                        <motion.div initial={{ opacity: 0 }} animate={{ opacity: 1 }}>
                            <h2 style={{ marginBottom: '25px', display: 'flex', alignItems: 'center', gap: '10px' }}>
                                <MapPin className="text-primary" /> Collateral & Valuation
                            </h2>
                            <div className="form-row">
                                <div className="form-group">
                                    <label>Fair Market Value (FMV)</label>
                                    <input 
                                        type="number" value={appraisalData.fair_market_value}
                                        onChange={(e) => setAppraisalData({...appraisalData, fair_market_value: e.target.value})}
                                    />
                                </div>
                                <div className="form-group">
                                    <label>Distress Value (DV)</label>
                                    <input 
                                        type="number" value={appraisalData.distress_value}
                                        onChange={(e) => setAppraisalData({...appraisalData, distress_value: e.target.value})}
                                    />
                                </div>
                            </div>
                            <div className="form-group">
                                <label>Collateral Location & Characteristics</label>
                                <textarea 
                                    rows="2" value={appraisalData.collateral_location}
                                    onChange={(e) => setAppraisalData({...appraisalData, collateral_location: e.target.value})}
                                    placeholder="Enter physical location details, accessibility, distance from main road..."
                                />
                            </div>
                        </motion.div>
                    )}

                    {activeTab === 'Risk' && (
                        <motion.div initial={{ opacity: 0 }} animate={{ opacity: 1 }}>
                            <h2 style={{ marginBottom: '25px', display: 'flex', alignItems: 'center', gap: '10px' }}>
                                <ShieldCheck className="text-primary" /> Risk Analysis & CRA
                            </h2>
                            <div className="form-row">
                                <div className="form-group">
                                    <label>CRA Score (Credit Risk Appraisal)</label>
                                    <input 
                                        type="range" min="0" max="100" value={appraisalData.cra_score}
                                        onChange={(e) => setAppraisalData({...appraisalData, cra_score: e.target.value})}
                                    />
                                    <div style={{ textAlign: 'center', fontWeight: '800' }}>{appraisalData.cra_score} / 100</div>
                                </div>
                                <div className="form-group">
                                    <label>Inspection Outcome</label>
                                    <select onChange={(e) => setAppraisalData({...appraisalData, unit_inspection_notes: e.target.value})}>
                                        <option value="Satisfactory">Satisfactory</option>
                                        <option value="Minor Lacuna">Minor Lacuna Noted</option>
                                        <option value="Not Satisfactory">Not Satisfactory</option>
                                    </select>
                                </div>
                            </div>
                            <div className="form-group">
                                <label>Mitigating Factors / Justification</label>
                                <textarea 
                                    rows="3" value={appraisalData.mitigating_factors}
                                    onChange={(e) => setAppraisalData({...appraisalData, mitigating_factors: e.target.value})}
                                    placeholder="Explain deviations or strong points..."
                                />
                            </div>
                        </motion.div>
                    )}

                    {activeTab === 'Final Recommendation' && (
                        <motion.div initial={{ opacity: 0 }} animate={{ opacity: 1 }}>
                            <h2 style={{ marginBottom: '25px', display: 'flex', alignItems: 'center', gap: '10px' }}>
                                <Calculator className="text-primary" /> Recommendation & Terms
                            </h2>
                            <div className="form-row">
                                <div className="form-group">
                                    <label>Recommended Limit (रु)</label>
                                    <input 
                                        type="number" value={appraisalData.recommended_limit}
                                        onChange={(e) => setAppraisalData({...appraisalData, recommended_limit: e.target.value})}
                                    />
                                </div>
                                <div className="form-group">
                                    <label>Interest Rate (%)</label>
                                    <input 
                                        type="number" step="0.1" value={appraisalData.interest_rate}
                                        onChange={(e) => setAppraisalData({...appraisalData, interest_rate: e.target.value})}
                                    />
                                </div>
                            </div>
                            <div className="form-group">
                                <label>Tenure (Months)</label>
                                <input 
                                    type="number" value={appraisalData.tenure_months}
                                    onChange={(e) => setAppraisalData({...appraisalData, tenure_months: e.target.value})}
                                />
                            </div>
                            
                            <div style={{ display: 'flex', justifyContent: 'flex-end', marginTop: '30px' }}>
                                <button type="submit" className="btn btn-primary" disabled={submitting} style={{ padding: '15px 50px' }}>
                                    Submit Appraisal & Finalize <CheckCircle size={18} />
                                </button>
                            </div>
                        </motion.div>
                    )}
                </form>
            </div>
        </div>
    );
};

export default AppraisalPage;
