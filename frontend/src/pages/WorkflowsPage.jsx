import React, { useState, useEffect } from 'react';
import api from '../api';
import { Search, GitMerge, ChevronRight, CheckCircle, XCircle, Clock, Info, User, FileText, Download } from 'lucide-react';

const WorkflowsPage = () => {
    const [workflows, setWorkflows] = useState([]);
    const [searchTerm, setSearchTerm] = useState('');
    const [selectedWf, setSelectedWf] = useState(null);
    const [workflowDetails, setWorkflowDetails] = useState(null);
    const [showReviewModal, setShowReviewModal] = useState(false);
    const [reviewForm, setReviewForm] = useState({
        status: 'Approved',
        feedback: '',
        confidence_level: 'High'
    });

    useEffect(() => {
        fetchWorkflows();
    }, []);

    const fetchWorkflows = async () => {
        try {
            const res = await api.get('/workflows');
            setWorkflows(res.data);
        } catch (err) {
            console.error('Error fetching workflows:', err);
        }
    };

    const handleViewDetails = async (wf) => {
        setSelectedWf(wf);
        try {
            const res = await api.get(`/workflows/${wf.lead_id}`);
            const appraisalRes = await api.get(`/appraisals/${wf.lead_id}`);
            setWorkflowDetails({
                ...res.data,
                appraisal: appraisalRes.data
            });
            setShowReviewModal(true);
        } catch (err) {
            console.error('Error fetching workflow details:', err);
        }
    };

    const handleSubmitReview = async () => {
        try {
            await api.post('/workflows/review', {
                lead_id: selectedWf.lead_id,
                ...reviewForm
            });
            setShowReviewModal(false);
            fetchWorkflows();
            alert('Review submitted successfully!');
        } catch (err) {
            console.error('Error submitting review:', err);
            alert('Failed to submit review');
        }
    };

    const filteredWorkflows = workflows.filter(wf => 
        (wf.customer_name?.toLowerCase() || '').includes(searchTerm.toLowerCase()) ||
        (wf.cap_id?.toLowerCase() || '').includes(searchTerm.toLowerCase()) ||
        (wf.lead_id?.toString() || '').includes(searchTerm.toLowerCase())
    );

    return (
        <div style={{ padding: '10px' }}>
            <div className="glass-card">
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '25px', flexWrap: 'wrap', gap: '15px' }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                        <GitMerge className="text-primary" />
                        <h3 style={{ margin: 0 }}>Active Workflows</h3>
                    </div>
                    <div style={{ position: 'relative', width: '400px' }}>
                        <Search size={18} style={{ position: 'absolute', left: '12px', top: '50%', transform: 'translateY(-50%)', color: 'var(--text-muted)' }} />
                        <input 
                            type="text" 
                            placeholder="Search by CAP ID, Lead ID or Customer..." 
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                            className="form-control"
                            style={{ paddingLeft: '40px' }}
                        />
                    </div>
                </div>

                <div className="table-container">
                    <table className="data-table">
                        <thead>
                            <tr>
                                <th>CAP ID</th>
                                <th>Customer Name</th>
                                <th>Current Step</th>
                                <th>Assigned To</th>
                                <th>Status</th>
                                <th style={{ textAlign: 'right' }}>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            {filteredWorkflows.length > 0 ? filteredWorkflows.map(wf => (
                                <tr key={wf.id}>
                                    <td>
                                        <span className="badge badge-primary">{wf.cap_id}</span>
                                    </td>
                                    <td className="font-bold">{wf.customer_name}</td>
                                    <td>
                                        <div style={{ display: 'flex', flexDirection: 'column' }}>
                                            <span className="font-bold">{wf.current_step}</span>
                                            <span className="text-xs color-muted">रु {Number(wf.proposed_limit).toLocaleString()}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span className="badge badge-neutral">{wf.assigned_role}</span>
                                    </td>
                                    <td>
                                        <span className={`badge ${
                                            wf.file_status === 'Pending' ? 'badge-pending' : 
                                            wf.file_status === 'Analysis' ? 'badge-primary' : 'badge-success'
                                        }`}>
                                            {wf.file_status}
                                        </span>
                                    </td>
                                    <td style={{ textAlign: 'right' }}>
                                        <button 
                                            className="btn btn-sm btn-primary"
                                            onClick={() => handleViewDetails(wf)}
                                            style={{ display: 'inline-flex', alignItems: 'center', gap: '5px' }}
                                        >
                                            <Info size={14} /> Review
                                        </button>
                                    </td>
                                </tr>
                            )) : (
                                <tr>
                                    <td colSpan="6" style={{ textAlign: 'center', padding: '30px', color: 'var(--text-muted)' }}>No workflows found.</td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            {/* Review Modal */}
            {showReviewModal && workflowDetails && (
                <div style={{
                    position: 'fixed', top: 0, left: 0, width: '100%', height: '100%',
                    background: 'rgba(0,0,0,0.5)', display: 'flex', justifyContent: 'flex-end', zIndex: 1000
                }}>
                    <div style={{
                        width: '600px', height: '100%', background: 'white', overflowY: 'auto',
                        padding: '30px', boxShadow: '-5px 0 15px rgba(0,0,0,0.1)',
                        animation: 'slideIn 0.3s ease-out'
                    }}>
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' }}>
                            <h2 style={{ margin: 0, fontWeight: '800' }}>Workflow Review</h2>
                            <button className="btn btn-secondary btn-sm" onClick={() => setShowReviewModal(false)}>Close</button>
                        </div>

                        {/* Appraisal Summary Section */}
                        <div className="glass-card" style={{ marginBottom: '20px', background: '#f8fafc' }}>
                            <h4 style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                                <FileText size={18} className="text-primary" /> Application Details
                            </h4>
                            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '15px' }}>
                                <div>
                                    <label style={{ fontSize: '0.75rem', color: 'var(--text-muted)' }}>CAP ID</label>
                                    <div style={{ fontWeight: '700' }}>{selectedWf.cap_id}</div>
                                </div>
                                <div>
                                    <label style={{ fontSize: '0.75rem', color: 'var(--text-muted)' }}>Customer</label>
                                    <div style={{ fontWeight: '700' }}>{selectedWf.customer_name}</div>
                                </div>
                                <div>
                                    <label style={{ fontSize: '0.75rem', color: 'var(--text-muted)' }}>Proposed Amount</label>
                                    <div style={{ fontWeight: '700', color: 'var(--accent)' }}>रु {Number(selectedWf.proposed_limit).toLocaleString()}</div>
                                </div>
                                <div>
                                    <label style={{ fontSize: '0.75rem', color: 'var(--text-muted)' }}>Authority Required</label>
                                    <div style={{ fontWeight: '700' }}>{workflowDetails.appraisal?.escalation?.approver_designation || 'Determining...'}</div>
                                </div>
                            </div>
                        </div>

                        {/* Review History */}
                        <div style={{ marginBottom: '30px' }}>
                            <h4 style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                                <Clock size={18} className="text-primary" /> Review History
                            </h4>
                            <div style={{ borderLeft: '2px dashed #e2e8f0', marginLeft: '10px', paddingLeft: '20px' }}>
                                {workflowDetails.reviews?.length > 0 ? workflowDetails.reviews.map((rev, idx) => (
                                    <div key={idx} style={{ position: 'relative', marginBottom: '20px' }}>
                                        <div style={{ 
                                            position: 'absolute', left: '-27px', top: '5px', 
                                            width: '12px', height: '12px', borderRadius: '50%', 
                                            background: rev.review_status === 'Approved' ? '#166534' : '#991b1b' 
                                        }} />
                                        <div style={{ fontWeight: '700' }}>{rev.level} Review - {rev.review_status}</div>
                                        <div style={{ fontSize: '0.85rem', color: 'var(--text-muted)' }}>
                                            By {rev.reviewer_name} on {new Date(rev.review_date).toLocaleDateString()}
                                        </div>
                                        {rev.feedback && (
                                            <div style={{ 
                                                marginTop: '5px', padding: '10px', background: '#f1f5f9', 
                                                borderRadius: '8px', fontSize: '0.9rem', fontStyle: 'italic' 
                                            }}>
                                                "{rev.feedback}"
                                            </div>
                                        )}
                                    </div>
                                )) : (
                                    <p style={{ color: 'var(--text-muted)' }}>No reviews yet. File is at initial stage.</p>
                                )}
                            </div>
                        </div>

                        {/* Action Section */}
                        <div className="glass-card" style={{ background: '#fff' }}>
                            <h4 style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                                <User size={18} className="text-primary" /> Your Review
                            </h4>
                            <div className="form-group">
                                <label>Decision</label>
                                <select 
                                    className="form-control"
                                    value={reviewForm.status}
                                    onChange={e => setReviewForm({...reviewForm, status: e.target.value})}
                                >
                                    <option value="Approved">Approve / Forward</option>
                                    <option value="Further Discussion">Request Further Info</option>
                                    <option value="Returned">Return to Initiator</option>
                                    <option value="Declined">Decline Application</option>
                                </select>
                            </div>
                            <div className="form-group">
                                <label>Comments / Feedback</label>
                                <textarea 
                                    className="form-control" 
                                    rows="4"
                                    placeholder="Enter your detailed comments here..."
                                    value={reviewForm.feedback}
                                    onChange={e => setReviewForm({...reviewForm, feedback: e.target.value})}
                                />
                            </div>
                            <button 
                                className="btn btn-accent btn-lg" 
                                style={{ width: '100%', marginTop: '10px' }}
                                onClick={handleSubmitReview}
                            >
                                <CheckCircle size={18} style={{ marginRight: '8px' }} /> Submit Decision
                            </button>
                        </div>
                    </div>
                </div>
            )}

            <style>{`
                @keyframes slideIn {
                    from { transform: translateX(100%); }
                    to { transform: translateX(0); }
                }
                .badge {
                    padding: 4px 10px;
                    border-radius: 20px;
                    font-size: 0.75rem;
                    font-weight: 600;
                }
                .badge-info { background: #e0f2fe; color: #075985; }
                .badge-warning { background: #fef3c7; color: #92400e; }
            `}</style>
        </div>
    );
};

export default WorkflowsPage;
