import React, { useState, useEffect } from 'react';
import api from '../api';
import { useAuth } from '../context/AuthContext';
import { 
    Search, Filter, Eye, Download, FileText, 
    CheckCircle, Clock, AlertCircle, XCircle, 
    LayoutGrid, List, Layers, ArrowLeft, Trash2, User as UserIcon, Building,
    Plus, ShieldAlert, BadgeCheck, Edit3, Send, RotateCcw, ShieldCheck, Activity
} from 'lucide-react';
import { motion, AnimatePresence } from 'framer-motion';
import { useNavigate } from 'react-router-dom';
import PermissionRequestModal from '../components/PermissionRequestModal';
import ProcessCICModal from '../components/ProcessCICModal';
import PermissionManager from '../components/PermissionManager';
import { toast } from 'react-hot-toast';

const CICPage = () => {
    const { user } = useAuth();
    const navigate = useNavigate();
    const [requests, setRequests] = useState([]);
    const [loading, setLoading] = useState(true);
    const [searchTerm, setSearchTerm] = useState('');
    const [statusFilter, setStatusFilter] = useState('All');
    const [showPermissionModal, setShowPermissionModal] = useState(false);
    const [selectedRequest, setSelectedRequest] = useState(null);
    const [showProcessModal, setShowProcessModal] = useState(false);

    useEffect(() => {
        fetchRequests();
    }, [statusFilter]);

    const fetchRequests = async () => {
        try {
            setLoading(true);
            const res = await api.get(`/cics/reconciliation?status=${statusFilter}`);
            setRequests(res.data || []);
        } catch (err) {
            console.error('Error fetching CIC requests:', err);
            toast.error('Failed to load CIC data');
        } finally {
            setLoading(false);
        }
    };

    const handleDelete = async (id) => {
        if (!window.confirm('Are you sure you want to delete this CIC request?')) return;
        try {
            await api.delete(`/cics/${id}`);
            toast.success('Request deleted');
            fetchRequests();
        } catch (err) {
            toast.error(err.response?.data?.error || 'Delete failed');
        }
    };

    const filteredRequests = (requests || []).filter(r => 
        (r.lead_id?.toLowerCase() || 'standalone').includes(searchTerm.toLowerCase()) ||
        r.branch_name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
        r.initiator_name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
        r.subjects?.some(s => s.subject_name?.toLowerCase().includes(searchTerm.toLowerCase()))
    );

    const getStatusStyle = (status) => {
        switch(status) {
            case 'Completed': return { bg: '#dcfce7', color: '#166534', icon: <CheckCircle size={14} /> };
            case 'Submitted': case 'Pending': case 'Processing': return { bg: '#fef3c7', color: '#92400e', icon: <Clock size={14} /> };
            case 'Returned': return { bg: '#ffedd5', color: '#ea580c', icon: <RotateCcw size={14} /> };
            case 'Draft': return { bg: '#f1f5f9', color: '#64748b', icon: <Edit3 size={14} /> };
            case 'Rejected': return { bg: '#fee2e2', color: '#b91c1c', icon: <XCircle size={14} /> };
            default: return { bg: '#f1f5f9', color: '#64748b', icon: <AlertCircle size={14} /> };
        }
    };

    // Role Logic:
    const isGenerator = user?.is_cic_generator || user?.role === 'Admin' || user?.role === 'HeadOffice';

    return (
        <div className="container" style={{ padding: '20px' }}>
            <header style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '30px' }}>
                <motion.div initial={{ opacity: 0, x: -20 }} animate={{ opacity: 1, x: 0 }}>
                    <h1 style={{ fontSize: '2.5rem', fontWeight: '900', margin: 0, background: 'linear-gradient(45deg, var(--primary), var(--accent))', WebkitBackgroundClip: 'text', WebkitTextFillColor: 'transparent', display: 'flex', alignItems: 'center', gap: '15px' }}>
                        <Activity size={40} color="var(--primary)" /> CIC Operational Hub
                    </h1>
                    <p style={{ color: 'var(--text-muted)', fontSize: '1.1rem' }}>Credit Information Management & Reporting Workflow</p>
                </motion.div>
                
                <div style={{ display: 'flex', gap: '15px', alignItems: 'center' }}>
                    {/* Role Status Tag */}
                    <div style={{ background: user?.is_cic_generator ? '#dcfce7' : '#f1f5f9', padding: '8px 15px', borderRadius: '12px', border: user?.is_cic_generator ? '1px solid #10b981' : '1px solid #e2e8f0', display: 'flex', alignItems: 'center', gap: '8px' }}>
                        {user?.is_cic_generator ? <BadgeCheck size={18} color="#10b981" /> : <ShieldAlert size={18} color="#64748b" />}
                        <div>
                            <p style={{ margin: 0, fontSize: '0.65rem', textTransform: 'uppercase', fontWeight: '700', color: '#64748b' }}>Current Role</p>
                            <p style={{ margin: 0, fontSize: '0.85rem', fontWeight: '800', color: '#1e293b' }}>{user?.is_cic_generator ? 'CIC Generator' : 'Standard Staff'}</p>
                        </div>
                    </div>

                    {!user?.is_cic_generator && user?.role === 'Branch Staff' && (
                        <button className="btn btn-secondary" onClick={() => setShowPermissionModal(true)} style={{ border: '1px solid var(--primary)', color: 'var(--primary)', background: '#fff' }}>
                            <ShieldCheck size={18} /> Request Generator Role
                        </button>
                    )}

                    {!user?.is_cic_generator && (user?.role === 'Branch Staff' || user?.role === 'Admin' || user?.role === 'Staff') && (
                        <button className="btn btn-primary" onClick={() => navigate('/cic/new')}>
                            <Plus size={18} /> New Standalone CIC
                        </button>
                    )}
                </div>
            </header>

            {user?.role === 'Admin' && <PermissionManager />}

            <div style={{ margin: '30px 0 25px 0', display: 'flex', justifyContent: 'space-between', alignItems: 'center', gap: '20px', flexWrap: 'wrap' }}>
                <div style={{ display: 'flex', gap: '8px', background: '#f1f5f9', padding: '5px', borderRadius: '15px' }}>
                    {['All', 'Draft', 'Submitted', 'Processing', 'Returned', 'Completed'].map(tab => (
                        <button 
                            key={tab}
                            onClick={() => setStatusFilter(tab)}
                            style={{ 
                                padding: '8px 16px', borderRadius: '12px', border: 'none', 
                                background: statusFilter === tab ? 'white' : 'transparent',
                                color: statusFilter === tab ? 'var(--primary)' : '#64748b',
                                fontWeight: '700', cursor: 'pointer', transition: 'all 0.2s',
                                boxShadow: statusFilter === tab ? '0 4px 10px rgba(0,0,0,0.05)' : 'none',
                                fontSize: '0.85rem'
                            }}
                        >
                            {tab}
                        </button>
                    ))}
                </div>

                <div style={{ position: 'relative', minWidth: '350px', display: 'flex', gap: '15px' }}>
                    <div style={{ position: 'relative', flex: 1 }}>
                        <Search size={18} style={{ position: 'absolute', left: '15px', top: '50%', transform: 'translateY(-50%)', color: 'var(--text-muted)' }} />
                        <input 
                            type="text" placeholder="Search by Lead, Branch, or Subject..." 
                            style={{ paddingLeft: '45px', width: '100%', background: 'white', border: '1px solid #e2e8f0', borderRadius: '12px', height: '45px' }}
                            value={searchTerm} onChange={(e) => setSearchTerm(e.target.value)}
                        />
                    </div>
                </div>
            </div>

            {loading ? (
                <div style={{ textAlign: 'center', padding: '100px 0' }}>
                    <div className="loader"></div>
                    <p style={{ marginTop: '20px', color: 'var(--text-muted)' }}>Refreshing Reconciliation View...</p>
                </div>
            ) : (
                <div className="table-container">
                    <table className="data-table">
                        <thead>
                            <tr>
                                <th>Lead / Ref</th>
                                <th>Involved Subjects</th>
                                <th>Originating Branch</th>
                                <th>Total Fee</th>
                                <th>Status</th>
                                <th style={{ textAlign: 'right' }}>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {filteredRequests.map((r, idx) => {
                                const statusStyle = getStatusStyle(r.status);
                                return (
                                    <tr key={r.id}>
                                        <td>
                                            <div style={{ fontWeight: '800', color: 'var(--primary)' }}>
                                                {r.lead_id ? (
                                                    <span onClick={() => navigate(`/leads/${r.lead_id}`)} style={{ cursor: 'pointer' }}>{r.lead_id}</span>
                                                ) : (
                                                    <span className="text-muted" style={{ fontSize: '0.75rem', fontWeight: '400', background: '#f1f5f9', padding: '2px 8px', borderRadius: '6px' }}>Standalone</span>
                                                )}
                                            </div>
                                            <div style={{ fontSize: '0.7rem', color: '#94a3b8' }}>{new Date(r.created_at).toLocaleDateString()}</div>
                                        </td>
                                        <td>
                                            <div style={{ display: 'flex', flexDirection: 'column', gap: '5px' }}>
                                                {r.subjects?.map((s, sIdx) => (
                                                    <div 
                                                        key={sIdx} 
                                                        onClick={() => navigate(`/cic/profile/${s.entity_type}/${s.entity_id}`)}
                                                        style={{ 
                                                            fontSize: '0.82rem', fontWeight: '600', color: '#1e293b', 
                                                            cursor: 'pointer', display: 'flex', alignItems: 'center', gap: '6px' 
                                                        }}
                                                    >
                                                        {s.entity_type === 'Personal' ? <UserIcon size={12} color="#64748b" /> : <Building size={12} color="#64748b" />}
                                                        {s.subject_name}
                                                    </div>
                                                ))}
                                            </div>
                                        </td>
                                        <td>
                                            <div style={{ fontWeight: '700' }}>{r.branch_name}</div>
                                            <div style={{ fontSize: '0.75rem', color: '#64748b' }}>{r.initiator_name}</div>
                                        </td>
                                        <td>
                                            <div style={{ fontWeight: '900', color: '#0f172a' }}>रु {(parseFloat(r.total_charge || 0) + parseFloat(r.vat_amount || 0)).toLocaleString()}</div>
                                            {r.payment_status === 'Paid' ? (
                                                <span style={{ fontSize: '0.65rem', color: '#10b981', fontWeight: '800' }}>● PAID: {r.transaction_id}</span>
                                            ) : (
                                                <span style={{ fontSize: '0.65rem', color: '#ef4444', fontWeight: '800' }}>● UNPAID</span>
                                            )}
                                        </td>
                                        <td>
                                            <div style={{ 
                                                display: 'flex', alignItems: 'center', gap: '6px', 
                                                padding: '6px 14px', borderRadius: '10px', fontSize: '0.75rem',
                                                background: statusStyle.bg, color: statusStyle.color, fontWeight: '800',
                                                width: 'fit-content', border: `1px solid ${statusStyle.color}40`
                                            }}>
                                                {statusStyle.icon}
                                                {r.status}
                                            </div>
                                        </td>
                                        <td style={{ textAlign: 'right' }}>
                                            <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '8px' }}>
                                                {(r.status === 'Draft' || r.status === 'Returned') && r.initiator_id === user?.id && (
                                                    <button className="action-btn" title="Edit/Resubmit" onClick={() => navigate(`/cic/edit/${r.id}`)} style={{ background: '#fffbeb', color: '#d97706' }}>
                                                        <Edit3 size={16} />
                                                    </button>
                                                )}
                                                
                                                {(r.status === 'Submitted' || r.status === 'Processing') && isGenerator && (
                                                    <button className="btn btn-sm btn-primary" onClick={() => { setSelectedRequest(r); setShowProcessModal(true); }} style={{ padding: '6px 12px', fontSize: '0.75rem' }}>
                                                        <FileText size={14} /> Process
                                                    </button>
                                                )}

                                                {r.report_url && (
                                                    <a href={r.report_url} target="_blank" rel="noopener noreferrer" className="action-btn" title="View Report" style={{ background: '#f0fdf4', color: '#15803d' }}>
                                                        <Download size={16} />
                                                    </a>
                                                )}
                                                
                                                {user?.role === 'Admin' && (
                                                    <button className="action-btn" title="Delete" style={{ background: '#fef2f2', color: '#dc2626' }} onClick={() => handleDelete(r.id)}>
                                                        <Trash2 size={16} />
                                                    </button>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>

                    {filteredRequests.length === 0 && (
                        <div style={{ textAlign: 'center', padding: '100px 0', color: 'var(--text-muted)' }}>
                            <FileText size={48} style={{ opacity: 0.3, marginBottom: '20px' }} />
                            <p>No CIC requests found for the selected criteria.</p>
                        </div>
                    )}
                </div>
            )}
            <PermissionRequestModal 
                isOpen={showPermissionModal}
                onClose={() => setShowPermissionModal(false)}
                onSuccess={() => {
                    toast.success('Your request is being reviewed by Admin');
                }}
            />

            <ProcessCICModal 
                isOpen={showProcessModal}
                onClose={() => { setShowProcessModal(false); setSelectedRequest(null); }}
                onSuccess={fetchRequests}
                request={selectedRequest}
            />
        </div>
    );
};

export default CICPage;
