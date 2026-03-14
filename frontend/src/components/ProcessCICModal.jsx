import React, { useState } from 'react';
import { X, Send, FileUp, AlertTriangle, CheckCircle, RotateCcw, MessageSquare, ExternalLink } from 'lucide-react';
import { motion } from 'framer-motion';
import api from '../api';
import toast from 'react-hot-toast';

const ProcessCICModal = ({ isOpen, onClose, onSuccess, request }) => {
    const [entitiesWithHits, setEntitiesWithHits] = useState(
        request?.subjects?.map(s => ({ ...s, is_hit: false })) || []
    );
    const [reportUrl, setReportUrl] = useState('');
    const [processorComment, setProcessorComment] = useState('');
    const [submitting, setSubmitting] = useState(false);

    const handleHitChange = (idx, value) => {
        const newEntities = [...entitiesWithHits];
        newEntities[idx].is_hit = value === 'Hit';
        setEntitiesWithHits(newEntities);
    };

    const handleComplete = async () => {
        if (!reportUrl) return toast.error('Please provide the CIC Report URL/ID');
        
        try {
            setSubmitting(true);
            await api.post('/cics/process', {
                request_id: request.id,
                entities_with_hits: entitiesWithHits.map(e => ({
                    entity_type: e.entity_type,
                    entity_id: e.entity_id,
                    is_hit: e.is_hit
                })),
                report_url: reportUrl,
                processor_comment: processorComment
            });
            toast.success('CIC Request Processed Successfully!');
            onSuccess();
            onClose();
        } catch (err) {
            toast.error(err.response?.data?.error || 'Processing failed');
        } finally {
            setSubmitting(false);
        }
    };

    const handleReturn = async () => {
        if (!processorComment) return toast.error('Please provide a comment for the return');

        try {
            setSubmitting(true);
            await api.post(`/cics/${request.id}/return`, {
                processor_comment: processorComment
            });
            toast.success('CIC Request Returned to Branch');
            onSuccess();
            onClose();
        } catch (err) {
            toast.error(err.response?.data?.error || 'Return failed');
        } finally {
            setSubmitting(false);
        }
    };

    if (!isOpen || !request) return null;

    return (
        <div className="modal-overlay" style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', background: 'rgba(0,0,0,0.5)', zIndex: 1000, position: 'fixed', top: 0, left: 0, width: '100vw', height: '100vh' }}>
            <motion.div 
                initial={{ scale: 0.9, opacity: 0 }}
                animate={{ scale: 1, opacity: 1 }}
                style={{ background: 'white', width: '800px', maxWidth: '95vw', borderRadius: '25px', boxShadow: '0 25px 50px rgba(0,0,0,0.3)', maxHeight: '90vh', overflowY: 'auto' }}
            >
                <div style={{ padding: '25px 30px', borderBottom: '1px solid #f1f5f9', display: 'flex', justifyContent: 'space-between', alignItems: 'center', background: 'linear-gradient(to right, #f8fafc, #fff)', borderRadius: '25px 25px 0 0' }}>
                    <div>
                        <h2 style={{ margin: 0, fontWeight: '800', color: 'var(--primary)', display: 'flex', alignItems: 'center', gap: '10px' }}>
                            <FileUp size={28} /> Process CIC Request
                        </h2>
                        <p style={{ margin: 0, fontSize: '0.85rem', color: '#64748b' }}>Verification & Report Generation</p>
                    </div>
                    <button onClick={onClose} style={{ border: 'none', background: 'none', cursor: 'pointer', color: '#94a3b8' }}><X size={24} /></button>
                </div>

                <div style={{ padding: '30px' }}>
                    {/* Initiator Data Section */}
                    <div style={{ marginBottom: '30px', background: '#f8fafc', padding: '20px', borderRadius: '15px', border: '1px solid #e2e8f0' }}>
                        <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '15px' }}>
                            <span style={{ fontSize: '0.8rem', color: '#64748b', fontWeight: '600' }}>INITIATOR DOCUMENTS</span>
                            <span style={{ fontSize: '0.8rem', color: '#64748b', fontWeight: '600' }}>REF: {request.transaction_id || 'N/A'}</span>
                        </div>
                        <div style={{ display: 'flex', gap: '10px', flexWrap: 'wrap', marginBottom: '15px' }}>
                            {request.initiator_docs_url?.map((doc, idx) => (
                                <a key={idx} href={doc.url} target="_blank" rel="noreferrer" style={{ textDecoration: 'none', display: 'flex', alignItems: 'center', gap: '5px', background: '#fff', border: '1px solid #e2e8f0', padding: '8px 12px', borderRadius: '8px', color: 'var(--primary)', fontSize: '0.85rem' }}>
                                    <ExternalLink size={14} /> {doc.name || 'Doc'}
                                </a>
                            ))}
                            {(!request.initiator_docs_url || request.initiator_docs_url.length === 0) && <p style={{ fontSize: '0.85rem', color: '#94a3b8', margin: 0 }}>No documents attached by branch.</p>}
                        </div>
                        {request.initiator_comment && (
                            <div style={{ display: 'flex', gap: '10px', background: '#fff', padding: '12px', borderRadius: '10px', border: '1px solid #f1f5f9' }}>
                                <MessageSquare size={16} color="#64748b" style={{ flexShrink: 0 }} />
                                <p style={{ margin: 0, fontSize: '0.85rem', color: '#475569' }}>
                                    <b>Branch Comment:</b> {request.initiator_comment}
                                </p>
                            </div>
                        )}
                    </div>

                    {/* Entities Verification Section */}
                    <div style={{ marginBottom: '30px' }}>
                        <h4 style={{ margin: '0 0 15px 0', fontWeight: '700', color: '#1e293b' }}>Subject Verification (Hit / No-Hit)</h4>
                        <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
                            {entitiesWithHits.map((ent, idx) => (
                                <div key={idx} style={{ padding: '15px 20px', background: '#fff', border: '1px solid #e2e8f0', borderRadius: '12px', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                                    <div>
                                        <p style={{ margin: 0, fontWeight: '700', color: '#334155' }}>{ent.subject_name}</p>
                                        <span style={{ fontSize: '0.75rem', color: '#64748b' }}>{ent.entity_type} Subject</span>
                                    </div>
                                    <div style={{ display: 'flex', gap: '10px' }}>
                                        <button 
                                            onClick={() => handleHitChange(idx, 'No-Hit')}
                                            style={{ padding: '8px 15px', borderRadius: '8px', border: ent.is_hit ? '1px solid #e2e8f0' : '2px solid #10b981', background: ent.is_hit ? '#fff' : '#f0fdf4', color: ent.is_hit ? '#64748b' : '#059669', fontWeight: '600', cursor: 'pointer', fontSize: '0.85rem' }}
                                        >
                                            No Hit
                                        </button>
                                        <button 
                                            onClick={() => handleHitChange(idx, 'Hit')}
                                            style={{ padding: '8px 15px', borderRadius: '8px', border: !ent.is_hit ? '1px solid #e2e8f0' : '2px solid #ef4444', background: !ent.is_hit ? '#fff' : '#fef2f2', color: !ent.is_hit ? '#64748b' : '#dc2626', fontWeight: '600', cursor: 'pointer', fontSize: '0.85rem' }}
                                        >
                                            Hit Found
                                        </button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>

                    <div className="grid-2" style={{ gap: '30px' }}>
                        <div className="form-group">
                            <label style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                                <FileUp size={16} /> CIC Report URL / ID
                            </label>
                            <input 
                                type="text" 
                                className="form-input" 
                                placeholder="Paste the generated report URL or ID..."
                                value={reportUrl}
                                onChange={e => setReportUrl(e.target.value)}
                                style={{ border: '2px solid var(--primary)' }}
                            />
                            <p style={{ margin: '5px 0 0 0', fontSize: '0.75rem', color: '#94a3b8' }}>This will be stored as the official CIC reference.</p>
                        </div>
                        <div className="form-group">
                            <label>Processor Remarks</label>
                            <textarea 
                                className="form-input" 
                                rows="3" 
                                placeholder="Final assessment or reason for return..."
                                value={processorComment}
                                onChange={e => setProcessorComment(e.target.value)}
                            ></textarea>
                        </div>
                    </div>
                </div>

                <div style={{ padding: '25px 30px', borderTop: '1px solid #f1f5f9', display: 'flex', justifyContent: 'flex-end', gap: '15px', background: '#f8fafc', borderRadius: '0 0 25px 25px' }}>
                    <button className="btn btn-secondary" onClick={onClose} disabled={submitting}>Cancel</button>
                    <button className="btn btn-secondary" onClick={handleReturn} disabled={submitting} style={{ border: '1px solid #ef4444', color: '#dc2626', background: '#fff' }}>
                        <RotateCcw size={18} /> Return to Branch
                    </button>
                    <button className="btn btn-primary" onClick={handleComplete} disabled={submitting} style={{ background: '#10b981', border: 'none' }}>
                        {submitting ? 'Completing...' : <><CheckCircle size={18} /> Complete Process</>}
                    </button>
                </div>
            </motion.div>
        </div>
    );
};

export default ProcessCICModal;
