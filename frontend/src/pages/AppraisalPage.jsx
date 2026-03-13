import React, { useState, useEffect } from 'react';
import { useParams, useNavigate, useLocation } from 'react-router-dom';
import api from '../api';
import { 
    FileText, Calculator, ShieldCheck, MapPin, 
    ArrowLeft, Save, CheckCircle, TrendingUp,
    Briefcase, Users, AlertTriangle, Search, Plus, 
    ChevronRight, ExternalLink, Filter, Download
} from 'lucide-react';
import { motion, AnimatePresence } from 'framer-motion';
import { toast } from 'react-hot-toast';

const AppraisalPage = () => {
    const { id } = useParams();
    const navigate = useNavigate();
    const location = useLocation();
    
    // View Management
    const [view, setView] = useState(id ? 'form' : 'list');
    const [appraisals, setAppraisals] = useState([]);
    const [leads, setLeads] = useState([]); // For direct creation picking
    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    
    // List & Search State
    const [searchQuery, setSearchQuery] = useState('');
    const [showCreateModal, setShowCreateModal] = useState(false);
    
    // Form State
    const [activeTab, setActiveTab] = useState('Borrower Info');
    const [appraisalData, setAppraisalData] = useState({
        loan_type: 'New Loan',
        borrower_details: {
            age: '', phone: '', pan: '', group_indebtedness: 0, relation: '',
            purpose: 'For personal Obligations', banking_since: ''
        },
        income_details: {
            agriculture_gross: 0, agriculture_net: 0,
            remittance_gross: 0, remittance_net: 0,
            salary_gross: 0, salary_tds: 0, salary_net: 0,
            rental_gross: 0, rental_net: 0,
            household_expenses: 0, emi_other_bfis: 0,
            total_uncommitted_income: 0, dti_ratio: 0
        },
        collateral_details: {
            plot_no: '', area: '', location_distance: '', accessibility: '',
            valuation_method: 'DV Calculation (80:20)', owner_name: ''
        },
        valuations: {
            fmv: 0, dv: 0, recommended_limit: 0, ltv_ratio: 0
        },
        risk_assessment: {
            cra_score: 0, mitigating_factors: '', inspection_notes: 'Satisfactory',
            cra_factors: {
                income_source: 10, dti: 15, collateral_dv: 5, collateral_fmv: 5,
                track_record: 5, collateral_importance: 7.5, exposure: 10, age: 15, banking_arrangement: 10
            }
        },
        pricing: {
            base_rate: 8.5, spread: 2.5, effective_rate: 11,
            processing_fee: 0.75, cic_fee: 550, upfront_fee: 0
        },
        final_recommendation: {
            justification: '', salient_features: '', confirm_verified: true
        }
    });

    useEffect(() => {
        if (id) {
            fetchAppraisal(id);
        } else {
            fetchAppraisals();
            fetchEligibleLeads();
        }
    }, [id]);

    const handleCreateBlank = async () => {
        try {
            const response = await api.post('/appraisals/blank', {});
            if (response.data && response.data.id) {
                navigate(`/appraisal/${response.data.id}`);
                toast.success('Blank Appraisal Initiated');
            }
        } catch (err) {
            console.error('Create Blank Error:', err);
            toast.error('Failed to create blank appraisal');
        }
    };

    const fetchAppraisals = async () => {
        try {
            const res = await api.get('/appraisals');
            setAppraisals(res.data);
            setLoading(false);
        } catch (err) {
            toast.error('Failed to fetch appraisals');
            setLoading(false);
        }
    };

    const fetchEligibleLeads = async () => {
        try {
            const res = await api.get('/leads');
            const eligible = res.data.filter(l => l.status === 'Analysis' || l.status === 'Appraisal');
            setLeads(eligible);
        } catch (err) {
            console.error('Error fetching leads');
        }
    };

    const fetchAppraisal = async (appId) => {
        setLoading(true);
        try {
            const res = await api.get(`/appraisals/${appId}`);
            if (res.data) {
                // Merge data with defaults
                setAppraisalData(prev => ({
                    ...prev,
                    ...res.data,
                    borrower_details: { ...prev.borrower_details, ...res.data.borrower_details },
                    income_details: { ...prev.income_details, ...res.data.income_details },
                    collateral_details: { ...prev.collateral_details, ...res.data.collateral_details },
                    valuations: { ...prev.valuations, ...res.data.valuations },
                    risk_assessment: { ...prev.risk_assessment, ...res.data.risk_assessment },
                    pricing: { ...prev.pricing, ...res.data.pricing },
                    final_recommendation: { ...prev.final_recommendation, ...res.data.final_recommendation }
                }));
            }
            setView('form');
        } catch (err) {
            toast.error('Appraisal not found');
            setView('list');
        } finally {
            setLoading(false);
        }
    };

    const handleCreateDirect = async (leadId) => {
        try {
            const res = await api.post('/appraisals/direct', { lead_id: leadId });
            toast.success('Appraisal Shell Created');
            navigate(`/appraisal/${leadId}`);
            setShowCreateModal(false);
        } catch (err) {
            toast.error(err.response?.data?.error || 'Creation failed');
        }
    };

    // Income Auto-Calculations
    useEffect(() => {
        const { agriculture_gross, remittance_gross, salary_gross, salary_tds, rental_gross, household_expenses, emi_other_bfis } = appraisalData.income_details;
        
        const agri_net = agriculture_gross * 0.75; // User: 70-80% consider
        const rem_net = remittance_gross * 1.0;  // User: No tax
        const sal_net = salary_gross - salary_tds; // User: TDS kata hai
        const rent_net = rental_gross * 0.9; // Standard 10% maintenance
        
        const total_net = agri_net + rem_net + sal_net + rent_net;
        const total_deductions = parseFloat(household_expenses) + parseFloat(emi_other_bfis);
        const uncommitted = total_net - total_deductions;
        const dti = total_net > 0 ? (total_deductions / total_net) * 100 : 0;
        
        setAppraisalData(prev => ({
            ...prev,
            income_details: {
                ...prev.income_details,
                agriculture_net: agri_net,
                remittance_net: rem_net,
                salary_net: sal_net,
                rental_net: rent_net,
                total_uncommitted_income: uncommitted,
                dti_ratio: dti
            }
        }));
    }, [
        appraisalData.income_details.agriculture_gross,
        appraisalData.income_details.remittance_gross,
        appraisalData.income_details.salary_gross,
        appraisalData.income_details.salary_tds,
        appraisalData.income_details.rental_gross,
        appraisalData.income_details.household_expenses,
        appraisalData.income_details.emi_other_bfis
    ]);

    const handleDownloadDocx = async () => {
        try {
            const response = await api.get(`/appraisals/export/docx/${id}`, { responseType: 'blob' });
            const url = window.URL.createObjectURL(new Blob([response.data]));
            const link = document.createElement('a');
            link.href = url;
            link.setAttribute('download', `Appraisal_${appraisalData.lead_identifier || id}.docx`);
            document.body.appendChild(link);
            link.click();
            link.remove();
        } catch (err) {
            toast.error('Download failed');
        }
    };

    const handleSubmit = async (e) => {
        if (e) e.preventDefault();
        setSubmitting(true);
        try {
            await api.post(`/appraisals/${id}`, appraisalData);
            toast.success('Appraisal Data Saved!');
            if (activeTab === 'Final Recommendation') {
                navigate(`/leads/${id}`);
            }
        } catch (err) {
            toast.error('Save failed');
        } finally {
            setSubmitting(false);
        }
    };

    if (loading && view === 'form') return <div className="p-20 text-center">Loading Appraisal Data...</div>;

    const filteredAppraisals = appraisals.filter(a => 
        a.lead_identifier?.toLowerCase().includes(searchQuery.toLowerCase()) ||
        a.customer_name?.toLowerCase().includes(searchQuery.toLowerCase())
    );

    return (
        <div className="container" style={{ padding: '20px' }}>
            {view === 'list' ? (
                /* LIST VIEW */
                <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '30px' }}>
                        <div>
                            <h1 style={{ fontWeight: '900', margin: 0, fontSize: '2.2rem' }}>Appraisal Management</h1>
                            <p style={{ color: 'var(--text-muted)' }}>Create and manage comprehensive credit appraisals</p>
                        </div>
                        <div style={{ display: 'flex', gap: '15px' }}>
                            <button className="btn btn-secondary" onClick={handleCreateBlank} style={{ padding: '12px 25px', display: 'flex', alignItems: 'center', gap: '8px' }}>
                                <Plus size={20} /> Blank Appraisal
                            </button>
                            <button className="btn btn-primary" onClick={() => setShowCreateModal(true)} style={{ padding: '12px 25px', display: 'flex', alignItems: 'center', gap: '8px' }}>
                                <Plus size={20} /> Pick From Lead
                            </button>
                        </div>
                    </div>

                    <div className="glass-card" style={{ padding: '25px', marginBottom: '30px' }}>
                        <div style={{ position: 'relative', marginBottom: '20px' }}>
                            <Search size={20} style={{ position: 'absolute', left: '15px', top: '50%', transform: 'translateY(-50%)', color: 'var(--text-muted)' }} />
                            <input 
                                type="text" placeholder="Search by Lead ID or Customer Name..." 
                                value={searchQuery} onChange={(e) => setSearchQuery(e.target.value)}
                                style={{ width: '100%', padding: '15px 15px 15px 50px', borderRadius: '15px', border: '1px solid #e2e8f0', fontSize: '1rem' }}
                            />
                        </div>

                        <div style={{ overflowX: 'auto' }}>
                            <table className="data-table">
                                <thead>
                                    <tr>
                                        <th>Lead ID</th>
                                        <th>Customer Name</th>
                                        <th>Branch</th>
                                        <th>Status</th>
                                        <th>Approved Limit</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {filteredAppraisals.map(app => (
                                        <tr key={app.id}>
                                            <td><span className="badge badge-info">{app.lead_identifier}</span></td>
                                            <td><div style={{ fontWeight: '700' }}>{app.customer_name}</div></td>
                                            <td>{app.branch_name}</td>
                                            <td>
                                                <span className={`badge bubble-${app.appraisal_status === 'Approved' ? 'success' : 'warning'}`}>
                                                    {app.appraisal_status}
                                                </span>
                                            </td>
                                            <td>रु {parseFloat(app.recommended_limit || 0).toLocaleString()}</td>
                                            <td style={{ fontSize: '0.8rem', color: 'var(--text-muted)' }}>{new Date(app.created_at).toLocaleDateString()}</td>
                                            <td>
                                                <button className="btn btn-sm btn-accent" onClick={() => navigate(`/appraisal/${app.lead_id}`)}>
                                                    Open <ExternalLink size={14} />
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                    {filteredAppraisals.length === 0 && (
                                        <tr>
                                            <td colSpan="7" style={{ textAlign: 'center', padding: '50px', color: 'var(--text-muted)' }}>
                                                No appraisals found matching your search.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </motion.div>
            ) : (
                /* FORM VIEW (9 Sections) */
                <div style={{ paddingBottom: '100px' }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '30px' }}>
                        <div>
                            <button onClick={() => navigate('/appraisals')} className="btn btn-secondary" style={{ marginBottom: '15px' }}>
                                <ArrowLeft size={18} /> Back to List
                            </button>
                            <h1 style={{ fontWeight: '900', margin: 0 }}>Full Mortgage Appraisal</h1>
                            <p style={{ color: 'var(--text-muted)' }}>Customer: <strong>{appraisalData.customer_name}</strong> | ID: {appraisalData.lead_identifier}</p>
                        </div>
                        <div style={{ display: 'flex', gap: '10px' }}>
                            <button className="btn btn-accent" onClick={handleDownloadDocx}>
                                Download Word <Download size={18} />
                            </button>
                            <button className="btn btn-secondary" onClick={() => handleSubmit()}>
                                Save Draft <Save size={18} />
                            </button>
                        </div>
                    </div>

                    <div style={{ display: 'grid', gridTemplateColumns: '280px 1fr', gap: '30px' }}>
                        {/* 9-Part Sidebar Navigation */}
                        <div style={{ position: 'sticky', top: '100px', height: 'fit-content' }}>
                            <div className="glass-card" style={{ padding: '15px', display: 'flex', flexDirection: 'column', gap: '5px' }}>
                                {[
                                    'Borrower Info', 'Repayment Sources', 'DTI & Calculations', 
                                    'Collateral Details', 'Valuations', 'Risk & CRA', 
                                    'Security Arng.', 'Pricing', 'Final Recommendation'
                                ].map((tab, idx) => (
                                    <button 
                                        key={tab}
                                        onClick={() => setActiveTab(tab)}
                                        style={{ 
                                            padding: '12px 15px', textAlign: 'left', borderRadius: '10px', border: 'none',
                                            background: activeTab === tab ? 'var(--primary)' : 'transparent',
                                            color: activeTab === tab ? 'white' : 'var(--text-main)',
                                            fontWeight: activeTab === tab ? '800' : '500', 
                                            cursor: 'pointer', transition: '0.2s',
                                            display: 'flex', alignItems: 'center', gap: '10px'
                                        }}
                                    >
                                        <span style={{ 
                                            width: '24px', height: '24px', borderRadius: '50%', 
                                            background: activeTab === tab ? 'rgba(255,255,255,0.2)' : 'var(--primary-light)',
                                            fontSize: '0.7rem', display: 'flex', alignItems: 'center', justifyContent: 'center'
                                        }}>{idx + 1}</span>
                                        {tab}
                                    </button>
                                ))}
                            </div>
                            
                            <div className="glass-card" style={{ marginTop: '20px', padding: '20px', background: 'var(--primary)', color: 'white' }}>
                                <div style={{ fontSize: '0.8rem', opacity: 0.8 }}>RECOMMENDED LIMIT</div>
                                <div style={{ fontSize: '1.5rem', fontWeight: '900' }}>रु {parseFloat(appraisalData.valuations.recommended_limit || 0).toLocaleString()}</div>
                                <div style={{ marginTop: '10px', borderTop: '1px solid rgba(255,255,255,0.2)', paddingTop: '10px' }}>
                                    <small>DTI: {parseFloat(appraisalData.income_details.dti_ratio || 0).toFixed(1)}%</small>
                                    <br/><small>CRA: {appraisalData.risk_assessment.cra_score}/100</small>
                                </div>
                            </div>
                        </div>

                        {/* Content Area */}
                        <div className="glass-card" style={{ padding: '40px' }}>
                            <AnimatePresence mode="wait">
                                <motion.div key={activeTab} initial={{ opacity: 0, x: 20 }} animate={{ opacity: 1, x: 0 }} exit={{ opacity: 0, x: -20 }}>
                                    {activeTab === 'Borrower Info' && (
                                        <>
                                            <h2 style={{ marginBottom: '25px', display: 'flex', alignItems: 'center', gap: '10px' }}>
                                                <Users className="text-primary" /> Borrower Profile
                                            </h2>
                                            <div className="form-group">
                                                <label>Appraisal Objective</label>
                                                <select value={appraisalData.loan_type} onChange={e => setAppraisalData({...appraisalData, loan_type: e.target.value})}>
                                                    <option>New Loan</option><option>Renewal</option><option>Enhancement</option><option>Restructuring</option>
                                                </select>
                                            </div>
                                            <div className="form-row">
                                                <div className="form-group"><label>Age</label><input type="number" value={appraisalData.borrower_details.age} onChange={e => setAppraisalData({...appraisalData, borrower_details: {...appraisalData.borrower_details, age: e.target.value}})} /></div>
                                                <div className="form-group"><label>PAN No.</label><input type="text" value={appraisalData.borrower_details.pan} onChange={e => setAppraisalData({...appraisalData, borrower_details: {...appraisalData.borrower_details, pan: e.target.value}})} /></div>
                                            </div>
                                            <div className="form-group">
                                                <label>Purpose of Loan</label>
                                                <textarea rows="2" value={appraisalData.borrower_details.purpose} onChange={e => setAppraisalData({...appraisalData, borrower_details: {...appraisalData.borrower_details, purpose: e.target.value}})} />
                                            </div>
                                            <div className="form-group">
                                                <label>Group Indebtedness (रु in Lacs)</label>
                                                <input type="number" value={appraisalData.borrower_details.group_indebtedness} onChange={e => setAppraisalData({...appraisalData, borrower_details: {...appraisalData.borrower_details, group_indebtedness: e.target.value}})} />
                                            </div>
                                        </>
                                    )}

                                    {activeTab === 'Repayment Sources' && (
                                        <>
                                            <h2 style={{ marginBottom: '25px', display: 'flex', alignItems: 'center', gap: '10px' }}>
                                                <Briefcase className="text-primary" /> Multi-Source Income Assessment
                                            </h2>
                                            <div style={{ background: '#f8fafc', padding: '20px', borderRadius: '15px' }}>
                                                <div className="form-row" style={{ marginBottom: '20px' }}>
                                                    <div className="form-group">
                                                        <label>Agriculture Gross (रु)</label>
                                                        <input type="number" value={appraisalData.income_details.agriculture_gross} onChange={e => setAppraisalData({...appraisalData, income_details: {...appraisalData.income_details, agriculture_gross: e.target.value}})} />
                                                        <small className="text-primary">Considered: {appraisalData.income_details.agriculture_net.toLocaleString()} (75%)</small>
                                                    </div>
                                                    <div className="form-group">
                                                        <label>Remittance Gross (रु)</label>
                                                        <input type="number" value={appraisalData.income_details.remittance_gross} onChange={e => setAppraisalData({...appraisalData, income_details: {...appraisalData.income_details, remittance_gross: e.target.value}})} />
                                                        <small className="text-primary">Considered: {appraisalData.income_details.remittance_net.toLocaleString()} (100%)</small>
                                                    </div>
                                                </div>
                                                <div className="form-row">
                                                    <div className="form-group">
                                                        <label>Salary Gross (रु)</label>
                                                        <input type="number" value={appraisalData.income_details.salary_gross} onChange={e => setAppraisalData({...appraisalData, income_details: {...appraisalData.income_details, salary_gross: e.target.value}})} />
                                                    </div>
                                                    <div className="form-group">
                                                        <label>TDS (रु)</label>
                                                        <input type="number" value={appraisalData.income_details.salary_tds} onChange={e => setAppraisalData({...appraisalData, income_details: {...appraisalData.income_details, salary_tds: e.target.value}})} />
                                                        <small className="text-primary">Net Salary: {appraisalData.income_details.salary_net.toLocaleString()}</small>
                                                    </div>
                                                </div>
                                                <div className="form-group" style={{ marginTop: '15px' }}>
                                                    <label>Rental Gross (रु)</label>
                                                    <input type="number" value={appraisalData.income_details.rental_gross} onChange={e => setAppraisalData({...appraisalData, income_details: {...appraisalData.income_details, rental_gross: e.target.value}})} />
                                                </div>
                                            </div>
                                        </>
                                    )}

                                    {activeTab === 'DTI & Calculations' && (
                                        <>
                                            <h2 style={{ marginBottom: '25px', display: 'flex', alignItems: 'center', gap: '10px' }}>
                                                <Calculator className="text-primary" /> Repayment Capacity (DTI)
                                            </h2>
                                            <div className="form-row">
                                                <div className="form-group"><label>Household Expenses (Proposed)</label><input type="number" value={appraisalData.income_details.household_expenses} onChange={e => setAppraisalData({...appraisalData, income_details: {...appraisalData.income_details, household_expenses: e.target.value}})} /></div>
                                                <div className="form-group"><label>Existing EMI/Outflow in other BFIs</label><input type="number" value={appraisalData.income_details.emi_other_bfis} onChange={e => setAppraisalData({...appraisalData, income_details: {...appraisalData.income_details, emi_other_bfis: e.target.value}})} /></div>
                                            </div>
                                            <div style={{ marginTop: '30px', padding: '30px', background: 'var(--primary)', color: 'white', borderRadius: '20px' }}>
                                                <div style={{ fontSize: '2.5rem', fontWeight: '950' }}>{parseFloat(appraisalData.income_details.dti_ratio || 0).toFixed(2)}%</div>
                                                <div style={{ fontWeight: '700' }}>Debt-To-Income Ratio</div>
                                                <p style={{ margin: '10px 0 0 0', opacity: 0.8 }}>Net uncommitted income: रु {appraisalData.income_details.total_uncommitted_income.toLocaleString()} per month.</p>
                                            </div>
                                        </>
                                    )}

                                    {activeTab === 'Collateral Details' && (
                                        <>
                                            <h2 style={{ marginBottom: '25px', display: 'flex', alignItems: 'center', gap: '10px' }}>
                                                <MapPin className="text-primary" /> Collateral Specifications
                                            </h2>
                                            <div className="form-row">
                                                <div className="form-group"><label>Plot No(s)</label><input type="text" value={appraisalData.collateral_details.plot_no} onChange={e => setAppraisalData({...appraisalData, collateral_details: {...appraisalData.collateral_details, plot_no: e.target.value}})} /></div>
                                                <div className="form-group"><label>Area (Sqr. Ft/Ropani)</label><input type="text" value={appraisalData.collateral_details.area} onChange={e => setAppraisalData({...appraisalData, collateral_details: {...appraisalData.collateral_details, area: e.target.value}})} /></div>
                                            </div>
                                            <div className="form-group"><label>Owner Name</label><input type="text" value={appraisalData.collateral_details.owner_name} onChange={e => setAppraisalData({...appraisalData, collateral_details: {...appraisalData.collateral_details, owner_name: e.target.value}})} /></div>
                                            <div className="form-group"><label>Distance from Main road / Accessibility</label><input type="text" value={appraisalData.collateral_details.accessibility} onChange={e => setAppraisalData({...appraisalData, collateral_details: {...appraisalData.collateral_details, accessibility: e.target.value}})} /></div>
                                        </>
                                    )}

                                    {activeTab === 'Valuations' && (
                                        <>
                                            <h2 style={{ marginBottom: '25px', display: 'flex', alignItems: 'center', gap: '10px' }}>
                                                <TrendingUp className="text-primary" /> FMV & DV Breakdown
                                            </h2>
                                            <div className="form-row">
                                                <div className="form-group"><label>Fair Market Value (FMV)</label><input type="number" value={appraisalData.valuations.fmv} onChange={e => setAppraisalData({...appraisalData, valuations: {...appraisalData.valuations, fmv: e.target.value}})} /></div>
                                                <div className="form-group"><label>Distress Value (DV)</label><input type="number" value={appraisalData.valuations.dv} onChange={e => setAppraisalData({...appraisalData, valuations: {...appraisalData.valuations, dv: e.target.value}})} /></div>
                                            </div>
                                            <div className="form-group">
                                                <label>Recommended Limit (Based on LTV)</label>
                                                <input type="number" value={appraisalData.valuations.recommended_limit} onChange={e => setAppraisalData({...appraisalData, valuations: {...appraisalData.valuations, recommended_limit: e.target.value}})} />
                                            </div>
                                        </>
                                    )}

                                    {activeTab === 'Risk & CRA' && (
                                        <>
                                            <h2 style={{ marginBottom: '25px', display: 'flex', alignItems: 'center', gap: '10px' }}>
                                                <ShieldCheck className="text-primary" /> Risk Rating (CRA - 82.50 Model)
                                            </h2>
                                            <div className="form-group">
                                                <label>Final CRA Score (out of 100)</label>
                                                <input type="number" max="100" value={appraisalData.risk_assessment.cra_score} onChange={e => setAppraisalData({...appraisalData, risk_assessment: {...appraisalData.risk_assessment, cra_score: e.target.value}})} />
                                            </div>
                                            <div className="form-group">
                                                <label>Unit Inspection Outcome</label>
                                                <select value={appraisalData.risk_assessment.inspection_notes} onChange={e => setAppraisalData({...appraisalData, risk_assessment: {...appraisalData.risk_assessment, inspection_notes: e.target.value}})}>
                                                    <option>Satisfactory</option><option>Satisfactory with minor lacuna</option><option>Unsatisfactory</option>
                                                </select>
                                            </div>
                                            <div className="form-group"><label>Mitigating Factors</label><textarea rows="3" value={appraisalData.risk_assessment.mitigating_factors} onChange={e => setAppraisalData({...appraisalData, risk_assessment: {...appraisalData.risk_assessment, mitigating_factors: e.target.value}})} /></div>
                                        </>
                                    )}

                                    {activeTab === 'Security Arng.' && (
                                        <>
                                            <h2 style={{ marginBottom: '25px', display: 'flex', alignItems: 'center', gap: '10px' }}>
                                                <ShieldCheck className="text-primary" /> Security Arrangement
                                            </h2>
                                            <div className="form-group">
                                                <label>Type of Charge</label>
                                                <select defaultValue="Registered Mortgage">
                                                    <option>Registered Mortgage</option><option>Equitable Mortgage</option><option>Hypotheaction</option>
                                                </select>
                                            </div>
                                            <div className="form-group">
                                                <label>Insurance Details</label>
                                                <textarea rows="2" placeholder="Policy No, Amount, Expiry..." />
                                            </div>
                                            <div className="form-group">
                                                <label>Guarantors (if any)</label>
                                                <input type="text" placeholder="Name of Guarantor(s)" />
                                            </div>
                                        </>
                                    )}

                                    {activeTab === 'Pricing' && (
                                        <>
                                            <h2 style={{ marginBottom: '25px', display: 'flex', alignItems: 'center', gap: '10px' }}>
                                                <TrendingUp className="text-primary" /> Pricing & Charges
                                            </h2>
                                            <div className="form-row">
                                                <div className="form-group"><label>Base Rate (%)</label><input type="number" step="0.01" value={appraisalData.pricing.base_rate} onChange={e => setAppraisalData({...appraisalData, pricing: {...appraisalData.pricing, base_rate: e.target.value}})} /></div>
                                                <div className="form-group"><label>Spread (%)</label><input type="number" step="0.01" value={appraisalData.pricing.spread} onChange={e => setAppraisalData({...appraisalData, pricing: {...appraisalData.pricing, spread: e.target.value}})} /></div>
                                            </div>
                                            <div className="form-row">
                                                <div className="form-group"><label>Processing Fee (%)</label><input type="number" step="0.01" value={appraisalData.pricing.processing_fee} onChange={e => setAppraisalData({...appraisalData, pricing: {...appraisalData.pricing, processing_fee: e.target.value}})} /></div>
                                                <div className="form-group"><label>CIC Fee (रु)</label><input type="number" value={appraisalData.pricing.cic_fee} onChange={e => setAppraisalData({...appraisalData, pricing: {...appraisalData.pricing, cic_fee: e.target.value}})} /></div>
                                            </div>
                                        </>
                                    )}

                                    {activeTab === 'Final Recommendation' && (
                                        <>
                                            <h2 style={{ marginBottom: '25px', display: 'flex', alignItems: 'center', gap: '10px' }}>
                                                <CheckCircle className="text-primary" /> Final Recommendation
                                            </h2>
                                            <div className="form-group">
                                                <label>Salient Features of the Proposal</label>
                                                <textarea rows="4" value={appraisalData.final_recommendation.salient_features} onChange={e => setAppraisalData({...appraisalData, final_recommendation: {...appraisalData.final_recommendation, salient_features: e.target.value}})} />
                                            </div>
                                            <div className="form-group">
                                                <label>Final Justification / Remarks</label>
                                                <textarea rows="4" value={appraisalData.final_recommendation.justification} onChange={e => setAppraisalData({...appraisalData, final_recommendation: {...appraisalData.final_recommendation, justification: e.target.value}})} />
                                            </div>
                                            <label style={{ display: 'flex', alignItems: 'center', gap: '10px', marginTop: '20px', fontWeight: 'bold' }}>
                                                <input type="checkbox" checked={appraisalData.final_recommendation.confirm_verified} onChange={e => setAppraisalData({...appraisalData, final_recommendation: {...appraisalData.final_recommendation, confirm_verified: e.target.checked}})} />
                                                I confirm that all provided details and documents have been verified.
                                            </label>
                                        </>
                                    )}

                                    {/* Add buttons inside tabs to move next */}
                                    <div style={{ marginTop: '40px', display: 'flex', justifyContent: 'flex-end' }}>
                                        <button className="btn btn-primary" onClick={() => {
                                            const tabs = ['Borrower Info', 'Repayment Sources', 'DTI & Calculations', 'Collateral Details', 'Valuations', 'Risk & CRA', 'Security Arng.', 'Pricing', 'Final Recommendation'];
                                            const nextIdx = tabs.indexOf(activeTab) + 1;
                                            if (nextIdx < tabs.length) setActiveTab(tabs[nextIdx]);
                                            else handleSubmit();
                                        }}>
                                            {activeTab === 'Final Recommendation' ? 'Finalize & Submit' : 'Save & Continue'} <ChevronRight size={18} />
                                        </button>
                                    </div>
                                </motion.div>
                            </AnimatePresence>
                        </div>
                    </div>
                </div>
            )}

            {/* CREATE MODAL */}
            {showCreateModal && (
                <div className="modal-overlay" style={{ position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.5)', zIndex: 2000, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                    <div className="glass-card" style={{ width: '500px', padding: '30px', background: 'white' }}>
                        <h2 style={{ margin: '0 0 10px 0', fontWeight: '900' }}>Initiate New Appraisal</h2>
                        <p style={{ color: 'var(--text-muted)', marginBottom: '20px' }}>Select an eligible lead to start formal appraisal.</p>
                        
                        <div className="form-group">
                            <label>Pick Lead</label>
                            <select id="leadPicker" style={{ width: '100%', padding: '12px' }}>
                                <option value="">-- Select Lead --</option>
                                {leads.map(l => <option key={l.id} value={l.lead_id}>{l.customer_name} ({l.lead_id})</option>)}
                            </select>
                        </div>
                        
                        <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '10px', marginTop: '20px' }}>
                            <button className="btn btn-secondary" onClick={() => setShowCreateModal(false)}>Cancel</button>
                            <button className="btn btn-primary" onClick={() => handleCreateDirect(document.getElementById('leadPicker').value)}>Initiate Appraisal</button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
};

export default AppraisalPage;
