import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import api from '../api';
import { 
    Clock, User, Shield, CheckCircle, 
    XCircle, AlertCircle, Info, ArrowLeft, Send, 
    Activity, TrendingUp, AlertTriangle, FileText, CheckCircle2
} from 'lucide-react';
import { motion, AnimatePresence } from 'framer-motion';
import { useAuth } from '../context/AuthContext';

const LeadDetailsPage = () => {
    const { id } = useParams();
    const navigate = useNavigate();
    const { user } = useAuth();
    const [data, setData] = useState(null);
    const [workflow, setWorkflow] = useState(null);
    const [reviews, setReviews] = useState([]);
    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    
    // Review Form State
    const [reviewForm, setReviewForm] = useState({
        status: 'Approved',
        confidence_level: 'High',
        feedback: '',
        conditions: ''
    });

    // Phase 2 Form State
    const [verificationData, setVerificationData] = useState({
        verified_income: '',
        verified_collateral_value: '',
        cib_report_status: 'Clear',
        kyc_status: 'Completed',
        verification_notes: ''
    });

    useEffect(() => {
        fetchDetails();
    }, [id]);

    const fetchDetails = async () => {
        try {
            const [leadRes, workflowRes] = await Promise.all([
                api.get(`/leads/${id}/details`),
                api.get(`/workflows/${id}`)
            ]);
            setData(leadRes.data);
            setWorkflow(workflowRes.data.workflow);
            setReviews(workflowRes.data.reviews);
            setLoading(false);
        } catch (err) {
            console.error('Error fetching details');
            setLoading(false);
        }
    };

    const handleReviewSubmit = async (e) => {
        e.preventDefault();
        setSubmitting(true);
        try {
            await api.post('/workflows/review', { lead_id: data.lead_id, ...reviewForm });
            alert('Review submitted successfully!');
            fetchDetails();
        } catch (err) {
            alert(err.response?.data?.error || 'Error submitting review');
        } finally {
            setSubmitting(false);
        }
    };

    const handleVerificationSubmit = async (e) => {
        e.preventDefault();
        setSubmitting(true);
        try {
            await api.post(`/leads/${id}/verify`, verificationData);
            alert('Phase 2 Verification Successful. FCS Score Calculated!');
            fetchDetails();
        } catch (err) {
            alert(err.response?.data?.error || 'Error submitting verification');
        } finally {
            setSubmitting(false);
        }
    };

    if (loading) return <div className="p-20 text-center">Loading Lead Analysis...</div>;
    if (!data) return <div className="p-20 text-center text-danger">Lead not found in advanced registry</div>;

    const lead = data; // Backend returns merged object
    // Handle deviation_alerts which can be an array (JSONB) or string
    const alerts = Array.isArray(lead.deviation_alerts) 
        ? lead.deviation_alerts 
        : (typeof lead.deviation_alerts === 'string' && lead.deviation_alerts.length > 0 
            ? JSON.parse(lead.deviation_alerts) 
            : []);

    return (
        <div className="container" style={{ padding: '20px' }}>
            <motion.div initial={{ opacity: 0, x: -20 }} animate={{ opacity: 1, x: 0 }} style={{ marginBottom: '24px' }}>
                <button onClick={() => navigate('/leads')} className="btn" style={{ background: 'white', border: '1px solid var(--glass-border)', display: 'flex', alignItems: 'center', gap: '8px', fontWeight: '700' }}>
                    <ArrowLeft size={18} /> Back to Registry
                </button>
            </motion.div>

            <div style={{ display: 'grid', gridTemplateColumns: '1fr 400px', gap: '24px' }}>
                {/* Left Column: Core Info & Phase 2 Form */}
                <div style={{ display: 'flex', flexDirection: 'column', gap: '24px' }}>
                    {/* Header Card */}
                    <div className="glass-card" style={{ padding: '30px', borderTop: '5px solid var(--primary)' }}>
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                            <div>
                                <h1 style={{ fontSize: '2.4rem', fontWeight: '900', margin: 0 }}>{lead.customer_name}</h1>
                                <p style={{ color: 'var(--text-muted)', fontSize: '1.1rem', marginTop: '4px' }}>
                                    {lead.lead_id} | {lead.loan_type} ({lead.customer_type})
                                </p>
                            </div>
                            <div style={{ textAlign: 'right' }}>
                                <span style={{ 
                                    padding: '6px 18px', borderRadius: '30px', fontSize: '0.8rem', fontWeight: '900',
                                    background: lead.status === 'Ongoing' ? '#e0f2fe' : '#f1f5f9',
                                    color: lead.status === 'Ongoing' ? '#0369a1' : '#64748b'
                                }}>
                                    {lead.status.toUpperCase()}
                                </span>
                                <h2 style={{ fontSize: '1.8rem', fontWeight: '900', color: 'var(--primary)', margin: '10px 0 0 0' }}>
                                    रु {parseFloat(lead.proposed_limit || 0).toLocaleString()}
                                </h2>
                            </div>
                        </div>

                        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '20px', marginTop: '30px', padding: '20px', background: '#f8fafc', borderRadius: '16px' }}>
                            <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                                <Info size={20} className="text-primary" />
                                <div>
                                    <small style={{ display: 'block', color: 'var(--text-muted)', fontWeight: '700', fontSize: '0.65rem', textTransform: 'uppercase' }}>Income Source</small>
                                    <span style={{ fontWeight: '800' }}>{lead.income_source}</span>
                                </div>
                            </div>
                            <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                                <TrendingUp size={20} className="text-primary" />
                                <div>
                                    <small style={{ display: 'block', color: 'var(--text-muted)', fontWeight: '700', fontSize: '0.65rem', textTransform: 'uppercase' }}>Loan Scheme</small>
                                    <span style={{ fontWeight: '800' }}>{lead.loan_scheme || 'N/A'}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    {/* Hierarchical Review & Workflow Panel */}
                    <div className="glass-card" style={{ padding: '30px', borderLeft: '5px solid var(--primary)' }}>
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: '20px' }}>
                            <div>
                                <h3 style={{ margin: 0, fontWeight: '800', display: 'flex', alignItems: 'center', gap: '10px' }}>
                                    <Shield size={24} className="text-primary" /> Hierarchical Credit Review
                                </h3>
                                <p style={{ color: 'var(--text-muted)', fontSize: '0.9rem', marginTop: '4px' }}>
                                    Current Hierarchy: <span style={{ fontWeight: '700', color: 'var(--primary)' }}>{workflow?.current_step}</span>
                                </p>
                            </div>
                            <div style={{ textAlign: 'right' }}>
                                <small style={{ display: 'block', color: 'var(--text-muted)', fontSize: '0.7rem' }}>CURRENT HANDLER</small>
                                <span style={{ fontWeight: '800' }}>{workflow?.handler_name}</span>
                                <div style={{ fontSize: '0.75rem', color: 'var(--primary)' }}>{workflow?.handler_designation}</div>
                            </div>
                        </div>

                        {/* Submission Form for Current Handler */}
                        {workflow?.current_handler_id === user?.id && workflow?.file_status !== 'Approved' && (
                            <motion.div initial={{ opacity: 0, y: 10 }} animate={{ opacity: 1, y: 0 }} style={{ background: '#f8fafc', padding: '20px', borderRadius: '15px', border: '1px solid #e2e8f0', marginBottom: '20px' }}>
                                <h4 style={{ marginTop: 0, marginBottom: '15px', fontSize: '1rem' }}>Submit Formal Review</h4>
                                <form onSubmit={handleReviewSubmit}>
                                    <div className="form-row">
                                        <div className="form-group">
                                            <label>Review Status</label>
                                            <select value={reviewForm.status} onChange={(e) => setReviewForm({...reviewForm, status: e.target.value})}>
                                                <option value="Approved">Recommend Approval</option>
                                                <option value="Defended">Defend for Discussion</option>
                                                <option value="Further Discussion">Send back for Analysis</option>
                                                <option value="Declined">Decline Proposal</option>
                                            </select>
                                        </div>
                                        <div className="form-group">
                                            <label>Confidence Level</label>
                                            <select value={reviewForm.confidence_level} onChange={(e) => setReviewForm({...reviewForm, confidence_level: e.target.value})}>
                                                <option value="High">High Confidence (Reliable)</option>
                                                <option value="Medium">Medium (Moderate Risk)</option>
                                                <option value="Low">Low (Requires Observation)</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div className="form-group">
                                        <label>Transparent Feedback / Discussion</label>
                                        <textarea rows="2" value={reviewForm.feedback} onChange={(e) => setReviewForm({...reviewForm, feedback: e.target.value})} placeholder="Why is this lead good or bad?" required />
                                    </div>
                                    <div className="form-group">
                                        <label>Specific Conditions (e.g. Limit reduction, Phase wise)</label>
                                        <input type="text" value={reviewForm.conditions} onChange={(e) => setReviewForm({...reviewForm, conditions: e.target.value})} placeholder="e.g. Reduce limit by 20%, collateral visit required..." />
                                    </div>
                                    <div style={{ display: 'flex', justifyContent: 'flex-end', marginTop: '10px' }}>
                                        <button type="submit" className="btn btn-primary" disabled={submitting}>
                                            Submit & Push Forward <Send size={16} />
                                        </button>
                                    </div>
                                </form>
                            </motion.div>
                        )}

                        {/* Appraisal Trigger for Authorized Users */}
                        {workflow?.file_status === 'Approved' && lead.status === 'Analysis' && (
                             <div style={{ background: '#ecfdf5', padding: '20px', borderRadius: '15px', border: '1px solid #10b981', marginBottom: '20px', textAlign: 'center' }}>
                                <h4 style={{ color: '#065f46', margin: '0 0 10px 0' }}>Approved for Appraisal!</h4>
                                <p style={{ fontSize: '0.9rem', marginBottom: '15px' }}>The hierarchy has cleared this lead for formal appraisal module entry.</p>
                                <button className="btn btn-success" style={{ padding: '12px 30px' }} onClick={() => navigate(`/appraisal/${lead.lead_id}`)}>
                                    Go to Appraisal Module <CheckCircle size={18} />
                                </button>
                             </div>
                        )}

                        {/* Recent Reviews (Transparency) */}
                        {reviews.length > 0 && (
                            <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
                                <h4 style={{ fontSize: '0.8rem', color: 'var(--text-muted)', textTransform: 'uppercase', letterSpacing: '1px' }}>Previous Reviews</h4>
                                {reviews.map((rev, i) => (
                                    <div key={i} style={{ padding: '15px', background: 'white', border: '1px solid #eaf2f9', borderRadius: '12px' }}>
                                        <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                                            <span style={{ fontWeight: '800', fontSize: '0.9rem' }}>{rev.reviewer_name} ({rev.level})</span>
                                            <span style={{ 
                                                fontSize: '0.7rem', padding: '2px 8px', borderRadius: '4px', fontWeight: '900',
                                                background: rev.review_status === 'Approved' ? '#dcfce7' : '#fee2e2',
                                                color: rev.review_status === 'Approved' ? '#16a34a' : '#dc2626'
                                            }}>{rev.review_status.toUpperCase()}</span>
                                        </div>
                                        <div style={{ fontStyle: 'italic', fontSize: '0.85rem', marginTop: '5px', color: '#475569' }}>"{rev.feedback}"</div>
                                        {rev.conditions && (
                                            <div style={{ marginTop: '8px', fontSize: '0.8rem', background: '#fffbeb', padding: '5px 10px', borderRadius: '6px', border: '1px solid #fde68a' }}>
                                                <strong>Conditions:</strong> {rev.conditions}
                                            </div>
                                        )}
                                        <div style={{ textAlign: 'right', fontSize: '0.65rem', color: 'var(--text-muted)', marginTop: '5px' }}>
                                            Confidence: <strong>{rev.confidence_level}</strong> | {new Date(rev.review_date).toLocaleString()}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>

                    {/* Declared Qualification Metrics */}
                    <div className="glass-card" style={{ padding: '30px' }}>
                        <h3 style={{ marginBottom: '20px', display: 'flex', alignItems: 'center', gap: '12px', fontWeight: '800' }}>
                            <Activity size={24} className="text-primary" />
                            Declared Qualification Details
                        </h3>
                        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '20px' }}>
                            <div className="metric-box">
                                <small style={{ color: 'var(--text-muted)', fontSize: '0.7rem' }}>COLLATERAL TYPE</small>
                                <div style={{ fontWeight: '700' }}>{lead.collateral_type}</div>
                                <div style={{ fontSize: '0.9rem', color: 'var(--primary)', fontWeight: '800' }}> रु {parseFloat(lead.estimated_collateral_value || 0).toLocaleString()}</div>
                            </div>
                            <div className="metric-box">
                                <small style={{ color: 'var(--text-muted)', fontSize: '0.7rem' }}>FAMILY MEMBERS</small>
                                <div style={{ fontWeight: '700' }}>{lead.undivided_family_members} Members</div>
                                <div style={{ fontSize: '0.75rem', color: lead.undivided_family_members > 5 ? 'var(--warning)' : 'var(--success)' }}>
                                    {lead.undivided_family_members > 5 ? 'Undivided Family' : 'Nuclear Family'}
                                </div>
                            </div>
                            <div className="metric-box">
                                <small style={{ color: 'var(--text-muted)', fontSize: '0.7rem' }}>RISK FLAGS</small>
                                <div style={{ display: 'flex', gap: '8px', marginTop: '4px' }}>
                                    {lead.is_pep && <span style={{ background: '#fee2e2', color: '#dc2626', padding: '2px 8px', borderRadius: '4px', fontSize: '0.65rem', fontWeight: '900' }}>PEP</span>}
                                    {lead.has_legal_dispute && <span style={{ background: '#fee2e2', color: '#dc2626', padding: '2px 8px', borderRadius: '4px', fontSize: '0.65rem', fontWeight: '900' }}>LEGAL DISPUTE</span>}
                                    {!lead.is_pep && !lead.has_legal_dispute && <span style={{ background: '#dcfce7', color: '#16a34a', padding: '2px 8px', borderRadius: '4px', fontSize: '0.65rem', fontWeight: '900' }}>CLEAN</span>}
                                </div>
                            </div>
                        </div>

                        <div style={{ marginTop: '25px', padding: '20px', background: 'var(--bg-main)', borderRadius: '15px' }}>
                            <h4 style={{ margin: '0 0 15px 0', fontSize: '0.9rem' }}>Detailed Income Breakdown (Declared)</h4>
                            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '15px' }}>
                                <div>
                                    <small style={{ color: 'var(--text-muted)' }}>Primary</small>
                                    <div style={{ fontWeight: '800', fontSize: '1.1rem' }}>रु {parseFloat(lead.primary_income || 0).toLocaleString()}</div>
                                </div>
                                <div>
                                    <small style={{ color: 'var(--text-muted)' }}>Secondary</small>
                                    <div style={{ fontWeight: '800', fontSize: '1.1rem' }}>रु {parseFloat(lead.secondary_income || 0).toLocaleString()}</div>
                                </div>
                                <div>
                                    <small style={{ color: 'var(--text-muted)' }}>Other ({lead.other_income_source || 'None'})</small>
                                    <div style={{ fontWeight: '800', fontSize: '1.1rem' }}>रु {parseFloat(lead.other_income_amount || 0).toLocaleString()}</div>
                                </div>
                            </div>
                            <div style={{ marginTop: '15px', borderTop: '1px dashed var(--glass-border)', paddingTop: '10px' }}>
                                <small style={{ color: 'var(--text-muted)' }}>Total Declared Monthly Income</small>
                                <div style={{ fontWeight: '900', fontSize: '1.2rem', color: 'var(--primary)' }}>
                                    रु {(parseFloat(lead.primary_income || 0) + parseFloat(lead.secondary_income || 0) + parseFloat(lead.other_income_amount || 0)).toLocaleString()}
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Phase 2: Staff Verification Form (Only Admin for now) */}
                    {lead.status === 'Analysis' && user?.role === 'Admin' && (
                        <div className="glass-card" style={{ padding: '30px', borderTop: '5px solid var(--accent)' }}>
                            <h3 style={{ marginBottom: '25px', display: 'flex', alignItems: 'center', gap: '12px', fontWeight: '800' }}>
                                <FileText size={24} className="text-accent" />
                                Phase 2: Real Data Verification (Admin Only)
                            </h3>
                            <form onSubmit={handleVerificationSubmit}>
                                <div className="form-row">
                                    <div className="form-group">
                                        <label>Verified Monthly Income (रु)</label>
                                        <input 
                                            type="number" 
                                            value={verificationData.verified_income}
                                            onChange={(e) => setVerificationData({...verificationData, verified_income: e.target.value})}
                                            placeholder="Enter actual income" required 
                                        />
                                    </div>
                                    <div className="form-group">
                                        <label>Collateral Value (रु)</label>
                                        <input 
                                            type="number" 
                                            value={verificationData.verified_collateral_value}
                                            onChange={(e) => setVerificationData({...verificationData, verified_collateral_value: e.target.value})}
                                            placeholder="Estimated value" required 
                                        />
                                    </div>
                                </div>
                                <div className="form-row">
                                    <div className="form-group">
                                        <label>CIB Report Status</label>
                                        <select 
                                            value={verificationData.cib_report_status}
                                            onChange={(e) => setVerificationData({...verificationData, cib_report_status: e.target.value})}
                                        >
                                            <option value="Clear">Clear / Good</option>
                                            <option value="Minor Issues">Minor Delayed Payments</option>
                                            <option value="Blacklisted">Blacklisted / Default</option>
                                        </select>
                                    </div>
                                    <div className="form-group">
                                        <label>KYC Verification</label>
                                        <select 
                                            value={verificationData.kyc_status}
                                            onChange={(e) => setVerificationData({...verificationData, kyc_status: e.target.value})}
                                        >
                                            <option value="Completed">Completed & Authenticated</option>
                                            <option value="In Progress">Pending Documents</option>
                                            <option value="Failed">Discrepancy Found</option>
                                        </select>
                                    </div>
                                </div>
                                <div className="form-group">
                                    <label>Verification Notes / Site Visit Summary</label>
                                    <textarea 
                                        rows="3" 
                                        value={verificationData.verification_notes}
                                        onChange={(e) => setVerificationData({...verificationData, verification_notes: e.target.value})}
                                        placeholder="Explain deviation if any..." required
                                    />
                                </div>
                                <div style={{ display: 'flex', justifyContent: 'flex-end', marginTop: '20px' }}>
                                    <button type="submit" className="btn btn-accent" disabled={submitting} style={{ padding: '14px 40px' }}>
                                        Complete Phase 2 & Generate FCS <Send size={18} />
                                    </button>
                                </div>
                            </form>
                        </div>
                    )}

                    {/* Deviation Analysis (Only Admin) */}
                    {lead.deviation_percentage !== null && user?.role === 'Admin' && (
                        <div className="glass-card" style={{ padding: '30px', background: lead.deviation_percentage > 20 ? '#fff5f5' : '#f0fdf4' }}>
                            <h3 style={{ display: 'flex', alignItems: 'center', gap: '12px', marginBottom: '20px' }}>
                                <AlertTriangle size={24} color={lead.deviation_percentage > 20 ? '#c53030' : '#2f855a'} />
                                Data Integrity Analysis
                            </h3>
                            <div style={{ display: 'flex', alignItems: 'center', gap: '20px' }}>
                                <div style={{ 
                                    width: '100px', height: '100px', borderRadius: '50%',
                                    background: 'white', display: 'flex', alignItems: 'center', justifyContent: 'center',
                                    flexDirection: 'column', border: '5px solid #e2e8f0'
                                }}>
                                    <span style={{ fontSize: '1.5rem', fontWeight: '900' }}>{Math.round(lead.deviation_percentage)}%</span>
                                    <small style={{ fontSize: '0.6rem', color: 'var(--text-muted)' }}>DEVIATION</small>
                                </div>
                                <div style={{ flex: 1 }}>
                                    <p style={{ margin: 0, fontWeight: '700', fontSize: '1.1rem' }}>
                                        {lead.deviation_percentage > 20 ? 'High Discrepancy Detected!' : 'Reliable Declaration'}
                                    </p>
                                    <div style={{ marginTop: '10px', display: 'flex', flexWrap: 'wrap', gap: '8px' }}>
                                        {alerts.map((a, i) => (
                                            <span key={i} style={{ padding: '4px 12px', background: 'white', borderRadius: '8px', fontSize: '0.75rem', fontWeight: '600', border: '1px solid #e2e8f0' }}>
                                                ⚠️ {a}
                                            </span>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Onboarding Report (Final View) */}
                    {lead.status === 'Appraised' && (
                        <div className="glass-card" style={{ padding: '40px', background: 'white', border: '2px solid var(--success)', position: 'relative', overflow: 'hidden' }}>
                            <div style={{ position: 'absolute', top: '-20px', right: '-20px', opacity: 0.05 }}>
                                <ShieldCheck size={200} />
                            </div>
                            <div style={{ textAlign: 'center', marginBottom: '30px' }}>
                                <div style={{ background: 'var(--success)', color: 'white', padding: '10px 30px', borderRadius: '30px', display: 'inline-block', fontWeight: '900', fontSize: '0.9rem', marginBottom: '15px' }}>
                                    OFFICIAL ONBOARDING REPORT
                                </div>
                                <h2 style={{ fontWeight: '900', fontSize: '2rem' }}>Final Credit Assessment</h2>
                                <p style={{ color: 'var(--text-muted)' }}>This lead has been formally appraised and cleared for disbursement.</p>
                            </div>

                            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '30px', marginBottom: '40px' }}>
                                <div style={{ textAlign: 'center', padding: '20px', background: '#f0fdf4', borderRadius: '20px' }}>
                                    <small style={{ display: 'block', color: '#166534', fontWeight: '700' }}>FINAL RETAIL SCORE</small>
                                    <div style={{ fontSize: '3rem', fontWeight: '950', color: '#15803d' }}>{lead.fcs_score}</div>
                                    <span style={{ fontSize: '0.8rem', fontWeight: '800' }}>Investment Grade</span>
                                </div>
                                <div style={{ textAlign: 'center', padding: '20px', background: '#f8fafc', borderRadius: '20px', border: '1px solid #e2e8f0' }}>
                                    <small style={{ display: 'block', color: 'var(--text-muted)', fontWeight: '700' }}>APPROVED LIMIT</small>
                                    <div style={{ fontSize: '1.8rem', fontWeight: '900' }}>रु {parseFloat(lead.proposed_limit).toLocaleString()}</div>
                                    <span style={{ fontSize: '0.8rem', color: 'var(--primary)', fontWeight: '800' }}>Fixed Rate: 12.5%</span>
                                </div>
                                <div style={{ textAlign: 'center', padding: '20px', background: '#fffbeb', borderRadius: '20px', border: '1px solid #fde68a' }}>
                                    <small style={{ display: 'block', color: '#92400e', fontWeight: '700' }}>RISK DISCLOSURE</small>
                                    <div style={{ fontSize: '1.2rem', fontWeight: '900', marginTop: '10px' }}>{lead.risk_category}</div>
                                    <p style={{ fontSize: '0.7rem', color: '#b45309' }}>Requires collateral visit</p>
                                </div>
                            </div>

                            <div style={{ padding: '20px', background: '#f8fafc', borderRadius: '15px', border: '1px solid #e2e8f0' }}>
                                <h4 style={{ margin: '0 0 15px 0', fontSize: '1rem', display: 'flex', alignItems: 'center', gap: '8px' }}>
                                    <FileText size={18} className="text-primary" /> Appraiser's Concluding Remarks
                                </h4>
                                <p style={{ margin: 0, fontStyle: 'italic', color: '#444' }}>"Borrower demonstrates stable employment history with government background. Proposed limit is well within their repayment capacity (DTI &lt; 40%). Collateral appraisal is pending site visit but appears adequate based on market rate."</p>
                            </div>

                            <div style={{ display: 'flex', justifyContent: 'center', marginTop: '40px', gap: '15px' }}>
                                <button className="btn btn-secondary" style={{ padding: '12px 25px' }}>
                                    Download PDF <FileText size={18} />
                                </button>
                                <button className="btn btn-primary" style={{ padding: '12px 35px' }}>
                                    Generate Offer Letter <ChevronRight size={18} />
                                </button>
                            </div>
                        </div>
                    )}
                    
                    {/* Interaction Log (Only Admin) */}
                    {user?.role === 'Admin' && (
                        <div className="glass-card" style={{ padding: '30px' }}>
                            <h3 style={{ marginBottom: '20px', display: 'flex', alignItems: 'center', gap: '12px' }}>
                                <Clock size={24} className="text-primary" /> Audit Trail & Scoring Logs
                            </h3>
                            <div style={{ display: 'flex', flexDirection: 'column', gap: '15px' }}>
                                {lead.logs?.map((log, i) => (
                                    <div key={i} style={{ padding: '15px', background: '#f8fafc', borderRadius: '12px', border: '1px solid #eaf2f9', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                                        <div>
                                            <div style={{ fontStyle: 'italic', fontSize: '0.75rem', color: 'var(--text-muted)' }}>{new Date(log.created_at).toLocaleString()}</div>
                                            <div style={{ fontWeight: '800', marginTop: '4px' }}>{log.type}: {log.description}</div>
                                        </div>
                                        <div style={{ background: 'var(--primary)', color: 'white', padding: '5px 12px', borderRadius: '8px', fontWeight: '900' }}>
                                            +{log.points} LP
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
                </div>

                {/* Right Column: Scoring Breakdown (Only Admin) */}
                <div style={{ display: 'flex', flexDirection: 'column', gap: '24px' }}>
                    {user?.role === 'Admin' && (
                        <>
                            {/* Final Score Card */}
                            <div className="glass-card" style={{ 
                                padding: '30px', textAlign: 'center', 
                                background: 'linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%)', 
                                color: 'white' 
                            }}>
                                <h4 style={{ margin: 0, opacity: 0.9, textTransform: 'uppercase', letterSpacing: '2px', fontSize: '0.8rem' }}>Final Credit Score (FCS)</h4>
                                <div style={{ fontSize: '5rem', fontWeight: '950', margin: '15px 0' }}>
                                    {lead.fcs_score || '--'}
                                </div>
                                <p style={{ opacity: 0.8, fontSize: '0.9rem' }}>Comprehensive appraisal weightage</p>
                                
                                <div style={{ marginTop: '20px', padding: '15px', background: 'rgba(255,255,255,0.1)', borderRadius: '15px' }}>
                                    <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '10px' }}>
                                        <span>Risk Level</span>
                                        <span style={{ fontWeight: '900' }}>{lead.risk_category || 'TBD'}</span>
                                    </div>
                                    <div style={{ height: '8px', background: 'rgba(255,255,255,0.2)', borderRadius: '4px' }}>
                                        <div style={{ 
                                            width: `${lead.fcs_score || 0}%`, 
                                            height: '100%', 
                                            background: 'white', 
                                            borderRadius: '4px',
                                            transition: 'width 1s cubic-bezier(0.4, 0, 0.2, 1)' 
                                        }} />
                                    </div>
                                </div>
                            </div>

                            {/* Breakdown Cards */}
                            <div className="glass-card" style={{ padding: '25px' }}>
                                <h4 style={{ marginBottom: '20px', display: 'flex', alignItems: 'center', gap: '8px' }}>
                                    <TrendingUp size={20} className="text-primary" /> Score Breakdown
                                </h4>
                                
                                <div style={{ display: 'flex', flexDirection: 'column', gap: '20px' }}>
                                    <div>
                                        <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '5px' }}>
                                            <span style={{ fontSize: '0.85rem', fontWeight: '700' }}>Qualification (Phase 1)</span>
                                            <span style={{ fontWeight: '800' }}>{lead.lqs_score}/100</span>
                                        </div>
                                        <div style={{ height: '6px', background: '#f1f5f9', borderRadius: '3px' }}>
                                            <div style={{ width: `${lead.lqs_score}%`, height: '100%', background: 'var(--primary)', borderRadius: '3px' }} />
                                        </div>
                                    </div>

                                    <div>
                                        <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '5px' }}>
                                            <span style={{ fontSize: '0.85rem', fontWeight: '700' }}>Verification (Phase 2)</span>
                                            <span style={{ fontWeight: '800' }}>{lead.sv_score || 0}/100</span>
                                        </div>
                                        <div style={{ height: '6px', background: '#f1f5f9', borderRadius: '3px' }}>
                                            <div style={{ width: `${lead.sv_score || 0}%`, height: '100%', background: 'var(--accent)', borderRadius: '3px' }} />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </>
                    )}

                    {/* Verification Data Summary (Only Admin) */}
                    {lead.verified_income && user?.role === 'Admin' && (
                        <div className="glass-card" style={{ padding: '25px' }}>
                            <h4 style={{ marginBottom: '15px' }}>Verified Assets</h4>
                            <div style={{ display: 'flex', flexDirection: 'column', gap: '15px' }}>
                                <div>
                                    <small style={{ color: 'var(--text-muted)' }}>Verified Income</small>
                                    <div style={{ fontWeight: '800' }}>रु {parseFloat(lead.verified_income).toLocaleString()}</div>
                                </div>
                                <div>
                                    <small style={{ color: 'var(--text-muted)' }}>CIB Status</small>
                                    <div style={{ color: lead.cib_report_status === 'Clear' ? '#059669' : '#dc2626', fontWeight: '900' }}>
                                        {lead.cib_report_status}
                                    </div>
                                </div>
                                <div>
                                    <small style={{ color: 'var(--text-muted)' }}>KYC Status</small>
                                    <div style={{ fontWeight: '800' }}>{lead.kyc_status}</div>
                                </div>
                                <div>
                                    <small style={{ color: 'var(--text-muted)' }}>Verifier Note</small>
                                    <p style={{ margin: 0, fontSize: '0.85rem', color: '#475569', fontStyle: 'italic' }}>"{lead.verification_notes}"</p>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
};

export default LeadDetailsPage;
