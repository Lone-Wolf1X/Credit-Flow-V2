import React, { useState, useEffect } from 'react';
import api from '../api';
import { 
    Plus, Trash2, FileText, CheckCircle, 
    AlertTriangle, Download, Send, CreditCard, 
    History, User, Building, Landmark 
} from 'lucide-react';
import toast from 'react-hot-toast';

const CICModule = ({ lead, user }) => {
    const [requests, setRequests] = useState([]);
    const [showNewRequest, setShowNewRequest] = useState(false);
    const [loading, setLoading] = useState(true);
    
    // New Request State
    const [entities, setEntities] = useState([
        { entity_type: 'Personal', entity_id: null, name: lead?.customer_name, person_id: lead?.id }
    ]);
    const [transactionId, setTransactionId] = useState('');
    const [remarks, setRemarks] = useState('');

    // Processing State
    const [processingRequest, setProcessingRequest] = useState(null);
    const [hitEntities, setHitEntities] = useState([]);
    const [reportUrl, setReportUrl] = useState('');

    useEffect(() => {
        if (lead?.lead_id) {
            fetchRequests();
        }
    }, [lead]);

    const fetchRequests = async () => {
        try {
            const res = await api.get(`/cics/lead/${lead.lead_id}`);
            setRequests(res.data);
            setLoading(false);
        } catch (err) {
            toast.error('Failed to fetch CIC history');
            setLoading(false);
        }
    };

    const handleAddEntity = () => {
        setEntities([...entities, { entity_type: 'Personal', entity_id: '', name: '' }]);
    };

    const handleRemoveEntity = (index) => {
        setEntities(entities.filter((_, i) => i !== index));
    };

    const handleInitiate = async () => {
        if (!transactionId) {
            return toast.error('Transaction ID is required for payment proof');
        }
        
        try {
            await api.post('/cics/initiate', {
                lead_id: lead.lead_id,
                entities: entities.map(e => ({ entity_type: e.entity_type, entity_id: e.entity_id || 0 })),
                transaction_id: transactionId,
                remarks
            });
            toast.success('CIC Request Initiated!');
            setShowNewRequest(false);
            fetchRequests();
        } catch (err) {
            toast.error(err.response?.data?.error || 'Initiation failed');
        }
    };

    const startProcessing = (req) => {
        setProcessingRequest(req);
        setHitEntities(req.entities.map(e => ({ ...e, is_hit: false })));
        setReportUrl('');
    };

    const handleProcessComplete = async () => {
        try {
            await api.post('/cics/process', {
                request_id: processingRequest.id,
                entities_with_hits: hitEntities,
                report_url: reportUrl,
                remarks: 'Report Generated & Uploaded'
            });
            toast.success('CIC Report Uploaded Successfully!');
            setProcessingRequest(null);
            fetchRequests();
        } catch (err) {
            toast.error('Failed to process report');
        }
    };

    const calculateTotalFee = () => {
        const base = entities.length * 250;
        const vat = base * 0.13;
        return { base, vat, total: base + vat };
    };

    const { base, vat, total } = calculateTotalFee();

    const isProcessor = user?.designation === 'Credit Head' || user?.designation === 'Province Head' || user?.role === 'Admin';

    if (loading) return <div>Loading CIC records...</div>;

    return (
        <div className="glass-card" style={{ padding: '25px', marginTop: '20px' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' }}>
                <h3 style={{ margin: 0, fontWeight: '800', display: 'flex', alignItems: 'center', gap: '10px' }}>
                    <History size={22} color="var(--primary)" />
                    CIC Background Check & Workflow
                </h3>
                {!showNewRequest && !processingRequest && (
                    <button className="btn btn-primary" onClick={() => setShowNewRequest(true)}>
                        <Plus size={18} /> Initiate New Request
                    </button>
                )}
            </div>

            {processingRequest && (
                <div style={{ background: '#eff6ff', padding: '25px', borderRadius: '15px', border: '1px solid #bfdbfe' }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' }}>
                        <h4 style={{ margin: 0, fontWeight: '800' }}>Processing CIC Request #{processingRequest.id}</h4>
                        <button className="btn btn-sm btn-secondary" onClick={() => setProcessingRequest(null)}>Exit Processor</button>
                    </div>

                    <div className="grid-3" style={{ marginBottom: '25px' }}>
                        {processingRequest.entities.map((ent, idx) => (
                            <div key={idx} style={{ background: 'white', padding: '15px', borderRadius: '10px', border: '1px solid #dbeafe' }}>
                                <div className="text-xs color-muted">{ent.entity_type.toUpperCase()}</div>
                                <div className="font-bold" style={{ fontSize: '1.1rem', marginBottom: '10px' }}>{ent.entity_id === 0 ? lead.customer_name : `Unit ${idx+1}`}</div>
                                
                                <div className="form-group" style={{ marginBottom: 0 }}>
                                    <label className="text-xs">CIB Hit Result</label>
                                    <div style={{ display: 'flex', gap: '10px' }}>
                                        <button 
                                            className={`btn btn-sm ${hitEntities[idx]?.is_hit ? 'btn-danger' : 'btn-secondary'}`}
                                            style={{ flex: 1 }}
                                            onClick={() => {
                                                const nh = [...hitEntities];
                                                nh[idx].is_hit = true;
                                                setHitEntities(nh);
                                            }}
                                        >Hit Found (550)</button>
                                        <button 
                                            className={`btn btn-sm ${!hitEntities[idx]?.is_hit ? 'btn-success' : 'btn-secondary'}`}
                                            style={{ flex: 1 }}
                                            onClick={() => {
                                                const nh = [...hitEntities];
                                                nh[idx].is_hit = false;
                                                setHitEntities(nh);
                                            }}
                                        >No Hit (250)</button>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>

                    <div className="grid-3">
                        <div className="form-group" style={{ gridColumn: 'span 2' }}>
                            <label>Report Document URL / Link (PDF)</label>
                            <input 
                                type="text" 
                                className="form-input" 
                                placeholder="Paste PDF link or internal document path..."
                                value={reportUrl || ''}
                                onChange={e => setReportUrl(e.target.value)}
                            />
                        </div>
                        <div style={{ display: 'flex', alignItems: 'flex-end' }}>
                            <button className="btn btn-primary" style={{ width: '100%', height: '42px' }} onClick={handleProcessComplete} disabled={!reportUrl}>
                                <CheckCircle size={18} /> Complete & Dispatch Report
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {showNewRequest ? (
                <div style={{ background: '#f8fafc', padding: '20px', borderRadius: '12px' }}>
                    <h4 className="form-section-header">Request Details & Entity Selection</h4>
                    
                    <div className="grid-3" style={{ marginBottom: '20px' }}>
                        <div className="form-group">
                            <label>Lead / Primary Subject</label>
                            <input type="text" value={lead.customer_name} disabled className="form-input" />
                        </div>
                        <div className="form-group">
                            <label>Requesting Branch</label>
                            <input type="text" value={user.branch_name} disabled className="form-input" />
                        </div>
                        <div className="form-group">
                            <label>Payment Transaction ID (CBS)</label>
                            <input 
                                type="text" 
                                placeholder="Enter Transaction Ref..." 
                                className="form-input"
                                value={transactionId || ''}
                                onChange={e => setTransactionId(e.target.value)}
                                style={{ border: '2px solid var(--primary)' }}
                            />
                        </div>
                    </div>

                    <h4 className="form-section-header">Involved Persons / Units for CIB Hit</h4>
                    <div style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
                        {entities.map((ent, idx) => (
                            <div key={idx} className="grid-3" style={{ background: 'white', padding: '15px', borderRadius: '8px', border: '1px solid #e2e8f0' }}>
                                <div className="form-group">
                                    <label>Entity Type</label>
                                    <select 
                                        className="form-input"
                                        value={ent.entity_type || 'Personal'}
                                        onChange={e => {
                                            const newEnts = [...entities];
                                            newEnts[idx].entity_type = e.target.value;
                                            setEntities(newEnts);
                                        }}
                                    >
                                        <option value="Personal">Personal (Self/Director/Family)</option>
                                        <option value="Institutional">Institutional (Company/Firm)</option>
                                    </select>
                                </div>
                                <div className="form-group">
                                    <label>Full Name / Unit Name</label>
                                    <input 
                                        type="text" 
                                        className="form-input"
                                        value={ent.name || ''}
                                        onChange={e => {
                                            const newEnts = [...entities];
                                            newEnts[idx].name = e.target.value;
                                            setEntities(newEnts);
                                        }}
                                        placeholder="Enter name for search..."
                                    />
                                </div>
                                <div style={{ display: 'flex', alignItems: 'flex-end' }}>
                                    {idx > 0 && (
                                        <button className="btn btn-danger" onClick={() => handleRemoveEntity(idx)} style={{ width: '100%', height: '42px' }}>
                                            <Trash2 size={18} /> Remove
                                        </button>
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>

                    <button className="btn btn-secondary" onClick={handleAddEntity} style={{ marginTop: '15px', width: '100%', border: '2px dashed #cbd5e1' }}>
                        <Plus size={18} /> Add Another Director / Family Member / Unit
                    </button>

                    <h4 className="form-section-header">Fee Calculation (Auto-Generated)</h4>
                    <div className="grid-3" style={{ background: '#ecfdf5', padding: '15px', borderRadius: '8px', border: '1px solid #10b981' }}>
                        <div>
                            <span className="text-xs color-muted">Base Charge (Rs. 250 x {entities.length})</span>
                            <div className="font-bold">Rs. {base.toFixed(2)}</div>
                        </div>
                        <div>
                            <span className="text-xs color-muted">VAT (13%)</span>
                            <div className="font-bold">Rs. {vat.toFixed(2)}</div>
                        </div>
                        <div>
                            <span className="text-xs color-muted">Total Collected</span>
                            <div className="font-black" style={{ fontSize: '1.2rem', color: '#047857' }}>Rs. {total.toFixed(2)}</div>
                        </div>
                    </div>

                    <div className="form-group" style={{ marginTop: '20px' }}>
                        <label>Manual Loan History / Remarks from Customer</label>
                        <textarea 
                            className="form-input" 
                            rows="3" 
                            placeholder="Branch noted past loan details if any..."
                            value={remarks || ''}
                            onChange={e => setRemarks(e.target.value)}
                        ></textarea>
                    </div>

                    <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '15px', marginTop: '20px' }}>
                        <button className="btn btn-secondary" onClick={() => setShowNewRequest(false)}>Cancel</button>
                        <button className="btn btn-primary" onClick={handleInitiate} disabled={!transactionId}>
                            <Send size={18} /> Initiate to HO / Province
                        </button>
                    </div>
                </div>
            ) : !processingRequest && (
                <div className="table-container">
                    {requests.length === 0 ? (
                        <div className="text-center p-20 color-muted">
                            <History size={48} style={{ opacity: 0.1, marginBottom: '10px' }} />
                            <p>No CIC requests found for this lead.</p>
                        </div>
                    ) : (
                        <table className="data-table">
                            <thead>
                                <tr>
                                    <th>Initiated On</th>
                                    <th>Units</th>
                                    <th>Fee Status</th>
                                    <th>Trans ID</th>
                                    <th>Status</th>
                                    <th style={{ textAlign: 'right' }}>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {requests.map(r => (
                                    <tr key={r.id}>
                                        <td>
                                            <div className="font-bold">{new Date(r.created_at).toLocaleDateString()}</div>
                                            <div className="text-xs color-muted">{r.initiator_name || 'Initiator'} | {r.branch_name || 'Branch'}</div>
                                        </td>
                                        <td>{r.entities?.length || 0} Units</td>
                                        <td>
                                            <div className="font-bold">Rs. {(parseFloat(r.total_charge) + parseFloat(r.vat_amount)).toFixed(2)}</div>
                                            <span className={`badge ${r.payment_status === 'Paid' ? 'badge-success' : 'badge-danger'}`} style={{ fontSize: '0.7rem' }}>
                                                {r.payment_status}
                                            </span>
                                        </td>
                                        <td className="text-xs font-bold">{r.transaction_id || 'N/A'}</td>
                                        <td>
                                            <span className={`badge ${
                                                r.status === 'Completed' ? 'badge-success' : 
                                                r.status === 'Pending' ? 'badge-pending' : 'badge-danger'
                                            }`}>
                                                {r.status}
                                            </span>
                                        </td>
                                        <td style={{ textAlign: 'right' }}>
                                            {r.status === 'Completed' && (
                                                <button className="btn btn-sm btn-primary" onClick={() => window.open(r.report_url)}>
                                                    <Download size={14} /> Report
                                                </button>
                                            )}
                                            {r.status === 'Pending' && isProcessor && (
                                                <button className="btn btn-sm btn-accent" onClick={() => startProcessing(r)}>
                                                    <FileText size={14} /> Process
                                                </button>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </div>
            )}
        </div>
    );
};

export default CICModule;
