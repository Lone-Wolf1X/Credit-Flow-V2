import React, { useState, useEffect } from 'react';
import { Shield, Check, X, Clock, User, MessageSquare } from 'lucide-react';
import api from '../api';
import toast from 'react-hot-toast';

const PermissionManager = () => {
    const [requests, setRequests] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetchRequests();
    }, []);

    const fetchRequests = async () => {
        try {
            setLoading(true);
            const res = await api.get('/users/permission-requests');
            setRequests(res.data || []);
        } catch (err) {
            toast.error('Failed to load permission requests');
        } finally {
            setLoading(false);
        }
    };

    const handleReview = async (id, status) => {
        const comment = window.prompt(`Enter review comment for ${status}:`);
        if (comment === null) return;

        try {
            await api.put(`/users/permission-requests/${id}`, {
                status: status,
                admin_comment: comment
            });
            toast.success(`Request ${status} successfully`);
            fetchRequests();
        } catch (err) {
            toast.error(err.response?.data?.error || 'Action failed');
        }
    };

    if (loading) return <div>Loading requests...</div>;

    return (
        <div style={{ background: '#fff', borderRadius: '20px', padding: '25px', boxShadow: '0 10px 30px rgba(0,0,0,0.05)', marginTop: '30px' }}>
            <h3 style={{ margin: '0 0 20px 0', fontWeight: '800', display: 'flex', alignItems: 'center', gap: '10px', color: '#1e293b' }}>
                <Shield size={24} color="var(--primary)" /> Pending Role Requests
            </h3>
            
            {requests.length === 0 ? (
                <p style={{ textAlign: 'center', color: '#64748b', padding: '20px' }}>No pending permission requests.</p>
            ) : (
                <div style={{ display: 'flex', flexDirection: 'column', gap: '15px' }}>
                    {requests.map(req => (
                        <div key={req.id} style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', padding: '15px 20px', background: '#f8fafc', borderRadius: '15px', border: '1px solid #e2e8f0' }}>
                            <div style={{ display: 'flex', gap: '15px', alignItems: 'center' }}>
                                <div style={{ background: '#fff', padding: '10px', borderRadius: '12px', boxShadow: '0 2px 5px rgba(0,0,0,0.05)' }}>
                                    <User size={20} color="var(--primary)" />
                                </div>
                                <div>
                                    <p style={{ margin: 0, fontWeight: '700', color: '#334155' }}>{req.user_name}</p>
                                    <span style={{ fontSize: '0.75rem', color: '#64748b', display: 'flex', alignItems: 'center', gap: '5px' }}>
                                        <Clock size={12} /> {new Date(req.created_at).toLocaleDateString()}
                                    </span>
                                </div>
                                <div style={{ borderLeft: '1px solid #e2e8f0', paddingLeft: '15px', marginLeft: '10px' }}>
                                    <p style={{ margin: 0, fontSize: '0.85rem', color: '#475569', display: 'flex', alignItems: 'center', gap: '5px' }}>
                                        <MessageSquare size={14} /> <i>"{req.reason}"</i>
                                    </p>
                                </div>
                            </div>
                            <div style={{ display: 'flex', gap: '10px' }}>
                                <button onClick={() => handleReview(req.id, 'Rejected')} style={{ background: '#fee2e2', border: 'none', color: '#dc2626', padding: '8px 15px', borderRadius: '10px', cursor: 'pointer', display: 'flex', alignItems: 'center', gap: '5px', fontWeight: '600' }}>
                                    <X size={16} /> Reject
                                </button>
                                <button onClick={() => handleReview(req.id, 'Approved')} style={{ background: '#dcfce7', border: 'none', color: '#15803d', padding: '8px 15px', borderRadius: '10px', cursor: 'pointer', display: 'flex', alignItems: 'center', gap: '5px', fontWeight: '600' }}>
                                    <Check size={16} /> Approve
                                </button>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
};

export default PermissionManager;
