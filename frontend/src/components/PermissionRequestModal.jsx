import React, { useState } from 'react';
import { X, Send, ShieldCheck, Info } from 'lucide-react';
import { motion } from 'framer-motion';
import api from '../api';
import toast from 'react-hot-toast';

const PermissionRequestModal = ({ isOpen, onClose, onSuccess }) => {
    const [reason, setReason] = useState('');
    const [submitting, setSubmitting] = useState(false);

    const handleSubmit = async () => {
        if (!reason.trim()) return toast.error('Please provide a reason for the request');

        try {
            setSubmitting(true);
            await api.post('/users/permission-request', {
                permission_type: 'CIC_Generator',
                reason: reason
            });
            toast.success('Permission request submitted!');
            onSuccess();
            onClose();
        } catch (err) {
            toast.error(err.response?.data?.error || 'Failed to submit request');
        } finally {
            setSubmitting(false);
        }
    };

    if (!isOpen) return null;

    return (
        <div className="modal-overlay" style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', background: 'rgba(0,0,0,0.5)', zIndex: 1000, position: 'fixed', top: 0, left: 0, width: '100vw', height: '100vh' }}>
            <motion.div 
                initial={{ scale: 0.9, opacity: 0 }}
                animate={{ scale: 1, opacity: 1 }}
                style={{ background: 'white', width: '500px', maxWidth: '95vw', borderRadius: '20px', boxShadow: '0 20px 50px rgba(0,0,0,0.2)' }}
            >
                <div style={{ padding: '20px 25px', borderBottom: '1px solid #eee', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                    <h2 style={{ margin: 0, fontWeight: '800', color: 'var(--primary)', display: 'flex', alignItems: 'center', gap: '10px' }}>
                        <ShieldCheck size={24} /> Request Generator Role
                    </h2>
                    <button onClick={onClose} style={{ border: 'none', background: 'none', cursor: 'pointer', color: '#94a3b8' }}><X size={24} /></button>
                </div>

                <div style={{ padding: '25px' }}>
                    <div style={{ background: '#f0f9ff', border: '1px solid #bae6fd', padding: '15px', borderRadius: '12px', display: 'flex', gap: '15px', marginBottom: '20px' }}>
                        <Info size={20} color="#0369a1" style={{ flexShrink: 0 }} />
                        <p style={{ margin: 0, fontSize: '0.85rem', color: '#0369a1', lineHeight: '1.4' }}>
                            Requesting the <b>CIC Generator</b> role will allow you to process CIC reports and upload results. 
                            Note: Once approved, your <i>Initiation</i> (Input) capabilities will be restricted as per policy.
                        </p>
                    </div>

                    <div className="form-group">
                        <label>Reason for Request</label>
                        <textarea 
                            className="form-input" 
                            rows="4" 
                            placeholder="Explain why you need this module (e.g. Assigned to CIC Processing Department)..."
                            value={reason}
                            onChange={e => setReason(e.target.value)}
                        ></textarea>
                    </div>
                </div>

                <div style={{ padding: '20px 25px', borderTop: '1px solid #eee', display: 'flex', justifyContent: 'flex-end', gap: '15px' }}>
                    <button className="btn btn-secondary" onClick={onClose} disabled={submitting}>Cancel</button>
                    <button className="btn btn-primary" onClick={handleSubmit} disabled={submitting}>
                        {submitting ? 'Submitting...' : <><Send size={18} /> Send Request</>}
                    </button>
                </div>
            </motion.div>
        </div>
    );
};

export default PermissionRequestModal;
