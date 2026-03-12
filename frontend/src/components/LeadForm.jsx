import React, { useState, useEffect } from 'react';
import api from '../api';
import { useAuth } from '../context/AuthContext';
import { Search, Plus, X, UserCheck, ShieldCheck, CheckCircle } from 'lucide-react';

const LeadForm = ({ onLeadCreated, leadData }) => {
    const { user } = useAuth();
    const [formData, setFormData] = useState({
        customer_name: leadData?.customer_name || '',
        contact_number: leadData?.contact_number || '',
        relationship_date: '',
        address: leadData?.address || '',
        loan_segment: '',
        loan_type: leadData?.loan_type || '',
        loan_scheme: '',
        existing_limit: 0,
        proposed_limit: leadData?.proposed_limit || 0,
        approver_id: '',
        additional_info: leadData?.lead_details || ''
    });
    
    const [file, setFile] = useState(null);
    const [reviewerSearch, setReviewerSearch] = useState('');
    const [foundReviewer, setFoundReviewer] = useState(null);
    const [selectedReviewers, setSelectedReviewers] = useState([]);
    
    const [approverSearch, setApproverSearch] = useState('');
    const [foundApprover, setFoundApprover] = useState(null);
    const [selectedApprover, setSelectedApprover] = useState(null);

    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData({ ...formData, [name]: value });
    };

    const handleSearch = async (query, setFound) => {
        if (!query) return;
        try {
            const res = await api.get(`/users/search/${query}`);
            if (res.data.id === user.id) {
                alert('You cannot assign yourself.');
                setFound(null);
                return;
            }
            setFound(res.data);
        } catch (err) {
            setFound(null);
        }
    };

    const addReviewer = () => {
        if (foundReviewer && !selectedReviewers.find(r => r.id === foundReviewer.id)) {
            setSelectedReviewers([...selectedReviewers, foundReviewer]);
            setFoundReviewer(null);
            setReviewerSearch('');
        }
    };

    const selectApprover = () => {
        if (foundApprover) {
            setSelectedApprover(foundApprover);
            setFormData({ ...formData, approver_id: foundApprover.id });
            setFoundApprover(null);
            setApproverSearch('');
        }
    };

    const handleFileChange = (e) => setFile(e.target.files[0]);

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (selectedReviewers.length === 0) return alert('Please add at least one reviewer');
        if (!selectedApprover) return alert('Please select a final approver');

        const data = new FormData();
        Object.keys(formData).forEach(key => data.append(key, formData[key]));
        selectedReviewers.forEach(r => data.append('reviewer_ids[]', r.id));
        if (file) data.append('file', file);

        try {
            const endpoint = leadData ? `/leads/${leadData.id}/start-processing` : '/leads';
            const method = leadData ? 'put' : 'post';
            
            await api[method](endpoint, data, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
            
            alert('Loan Processing Started Successfully!');
            onLeadCreated();
        } catch (err) {
            alert('Error starting process');
        }
    };

    return (
        <div className="glass-card" style={{ marginBottom: '30px', borderTop: '4px solid var(--primary)' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '25px' }}>
                <h3 style={{ display: 'flex', alignItems: 'center', gap: '12px', margin: 0 }}>
                    <ShieldCheck size={26} className="text-primary" />
                    Loan Initiation & Processing
                </h3>
                <div style={{ textAlign: 'right', background: 'var(--bg-main)', padding: '8px 15px', borderRadius: '10px', border: '1px solid var(--glass-border)' }}>
                    <span style={{ fontSize: '0.75rem', color: 'var(--text-muted)', display: 'block', textTransform: 'uppercase', letterSpacing: '0.5px' }}>RM/Initiator</span>
                    <span style={{ fontWeight: '700', fontSize: '1rem', color: 'var(--primary)' }}>{user?.name}</span>
                </div>
            </div>

            <form onSubmit={handleSubmit}>
                <div className="form-row">
                    <div className="form-group">
                        <label>Applicant Full Name</label>
                        <input name="customer_name" value={formData.customer_name} onChange={handleChange} required />
                    </div>
                    <div className="form-group">
                        <label>Contact Number</label>
                        <input name="contact_number" value={formData.contact_number} onChange={handleChange} required />
                    </div>
                    <div className="form-group">
                        <label>Relationship Date</label>
                        <input type="date" name="relationship_date" value={formData.relationship_date} onChange={handleChange} required />
                    </div>
                </div>

                <div className="form-row">
                    <div className="form-group">
                        <label>Physical Address</label>
                        <input name="address" value={formData.address} onChange={handleChange} required />
                    </div>
                    <div className="form-group">
                        <label>Loan Segment</label>
                        <select name="loan_segment" value={formData.loan_segment} onChange={handleChange} required>
                            <option value="">Select Segment</option>
                            <option value="Retail">Retail</option>
                            <option value="SME">SME</option>
                            <option value="MSME">MSME</option>
                            <option value="Micro">Micro</option>
                            <option value="Agriculture">Agriculture</option>
                            <option value="Guarantee">Guarantee</option>
                        </select>
                    </div>
                    <div className="form-group">
                        <label>Loan Type</label>
                        <input name="loan_type" value={formData.loan_type} onChange={handleChange} required />
                    </div>
                </div>

                <div className="form-row">
                    <div className="form-group">
                        <label>Loan Scheme</label>
                        <input name="loan_scheme" value={formData.loan_scheme} onChange={handleChange} placeholder="Scheme name" required />
                    </div>
                    <div className="form-group">
                        <label>Existing Limit (रु)</label>
                        <input type="number" name="existing_limit" value={formData.existing_limit} onChange={handleChange} required />
                    </div>
                    <div className="form-group">
                        <label>Loan Amount (रु)</label>
                        <input type="number" name="proposed_limit" value={formData.proposed_limit} onChange={handleChange} required />
                    </div>
                </div>

                <div className="form-row" style={{ alignItems: 'flex-start' }}>
                    <div className="form-group">
                        <label>Reviewers (ID/Email)</label>
                        <div style={{ position: 'relative', marginBottom: '10px' }}>
                            <Search size={16} style={{ position: 'absolute', left: '12px', top: '50%', transform: 'translateY(-50%)', color: 'var(--text-muted)' }} />
                            <input 
                                type="text" 
                                placeholder="Search & Add..." 
                                value={reviewerSearch}
                                onChange={(e) => {
                                    setReviewerSearch(e.target.value);
                                    handleSearch(e.target.value, setFoundReviewer);
                                }}
                                style={{ paddingLeft: '35px' }}
                            />
                        </div>
                        {foundReviewer && (
                            <div className="found-box" onClick={addReviewer} style={{ background: '#f0fdf4', padding: '8px 12px', borderRadius: '8px', border: '1px solid #bbf7d0', cursor: 'pointer', marginBottom: '10px', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                                <span style={{ fontSize: '0.85rem' }}>{foundReviewer.name} <i>({foundReviewer.designation})</i> <b>(Add +)</b></span>
                            </div>
                        )}
                        <div style={{ display: 'flex', flexWrap: 'wrap', gap: '8px' }}>
                            {selectedReviewers.map(r => (
                                <div key={r.id} style={{ background: 'var(--primary)', color: 'white', padding: '4px 10px', borderRadius: '6px', fontSize: '0.8rem', display: 'flex', alignItems: 'center', gap: '6px' }}>
                                    {r.name} ({r.designation}) <X size={12} style={{ cursor: 'pointer' }} onClick={() => setSelectedReviewers(selectedReviewers.filter(v => v.id !== r.id))} />
                                </div>
                            ))}
                        </div>
                    </div>

                    <div className="form-group">
                        <label>Final Approver (ID/Email)</label>
                        {!selectedApprover ? (
                            <>
                                <div style={{ position: 'relative', marginBottom: '10px' }}>
                                    <Search size={16} style={{ position: 'absolute', left: '12px', top: '50%', transform: 'translateY(-50%)', color: 'var(--text-muted)' }} />
                                    <input 
                                        type="text" 
                                        placeholder="Search Approver..." 
                                        value={approverSearch}
                                        onChange={(e) => {
                                            setApproverSearch(e.target.value);
                                            handleSearch(e.target.value, setFoundApprover);
                                        }}
                                        style={{ paddingLeft: '35px' }}
                                    />
                                </div>
                                {foundApprover && (
                                    <div className="found-box" onClick={selectApprover} style={{ background: '#eff6ff', padding: '8px 12px', borderRadius: '8px', border: '1px solid #bfdbfe', cursor: 'pointer', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                                        <span style={{ fontSize: '0.85rem' }}>{foundApprover.name} <i>({foundApprover.designation})</i> <b>(Select)</b></span>
                                    </div>
                                )}
                            </>
                        ) : (
                            <div style={{ background: '#e0f2fe', padding: '10px 15px', borderRadius: '10px', border: '2px solid var(--primary)', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                                <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                                    <CheckCircle size={18} className="text-primary" />
                                    <span style={{ fontWeight: '700', color: 'var(--primary)' }}>{selectedApprover.name} ({selectedApprover.designation})</span>
                                </div>
                                <X size={16} style={{ cursor: 'pointer', color: 'var(--danger)' }} onClick={() => setSelectedApprover(null)} />
                            </div>
                        )}
                    </div>

                    <div className="form-group">
                        <label>Supportive Document</label>
                        <input type="file" onChange={handleFileChange} style={{ background: 'none', border: '1px dashed #cbd5e1', padding: '8px' }} />
                    </div>
                </div>

                <div className="form-group" style={{ flex: '1 1 100%', marginBottom: '25px', marginTop: '15px' }}>
                    <label>Additional Information / Remarks</label>
                    <textarea name="additional_info" value={formData.additional_info} onChange={handleChange} rows="2" style={{ width: '100%' }}></textarea>
                </div>

                <div style={{ display: 'flex', justifyContent: 'flex-end', borderTop: '1px solid var(--glass-border)', paddingTop: '20px' }}>
                    <button type="submit" className="btn btn-primary" style={{ padding: '14px 60px', borderRadius: '12px', fontWeight: 'bold' }}>
                        {leadData ? 'Start Loan Processing' : 'Initiate Application'}
                    </button>
                </div>
            </form>
        </div>
    );
};

export default LeadForm;
