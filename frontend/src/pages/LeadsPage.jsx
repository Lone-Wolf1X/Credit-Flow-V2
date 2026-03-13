import React, { useState, useEffect } from 'react';
import api from '../api';
import InitialLeadForm from '../components/InitialLeadForm';
import LeadForm from '../components/LeadForm';
import { useAuth } from '../context/AuthContext';
import { 
    Plus, List, Search, Play, Forward, Clock, 
    UserPlus, Edit, CheckCircle, AlertCircle, XCircle, Info,
    Eye, Filter, ChevronRight, LayoutGrid, Layers, User, ArrowLeft
} from 'lucide-react';
import { motion, AnimatePresence } from 'framer-motion';
import { useNavigate } from 'react-router-dom';

const LeadsPage = () => {
    const { user } = useAuth();
    const navigate = useNavigate();
    const [leads, setLeads] = useState([]);
    const [showForm, setShowForm] = useState(false);
    const [editingLead, setEditingLead] = useState(null);
    const [processingLead, setProcessingLead] = useState(null);
    const [searchTerm, setSearchTerm] = useState('');
    const [activeTab, setActiveTab] = useState('Active'); // Draft, Active, Converted, Rejected
    const [viewMode, setViewMode] = useState('table'); // 'grid' or 'table'

    useEffect(() => {
        fetchLeads();
    }, []);

    const fetchLeads = async () => {
        try {
            const res = await api.get('/leads');
            setLeads(res.data);
        } catch (err) {
            console.error('Error fetching leads');
        }
    };

    const filteredLeads = leads.filter(l => {
        const matchesSearch = l.customer_name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
                             l.lead_id?.toLowerCase().includes(searchTerm.toLowerCase());
        
        if (activeTab === 'Draft') return matchesSearch && l.is_draft;
        if (activeTab === 'Converted') return matchesSearch && l.status === 'Converted';
        if (activeTab === 'Rejected') return matchesSearch && l.status === 'Rejected';
        return matchesSearch && !l.is_draft && l.status !== 'Converted' && l.status !== 'Rejected';
    });

    const getStatusStyle = (status) => {
        switch(status) {
            case 'Converted': return { bg: '#dcfce7', color: '#166534' };
            case 'Rejected': return { bg: '#fee2e2', color: '#b91c1c' };
            case 'Draft': return { bg: '#f1f5f9', color: '#64748b' };
            case 'Under Review': return { bg: '#e0f2fe', color: '#0369a1' };
            default: return { bg: '#fef3c7', color: '#92400e' };
        }
    };

    if (processingLead) {
        return (
            <div className="container" style={{ padding: '20px' }}>
                <div style={{ marginBottom: '20px', display: 'flex', alignItems: 'center', gap: '15px' }}>
                    <button className="btn btn-secondary" onClick={() => setProcessingLead(null)}>
                        <ArrowLeft size={18} /> Back
                    </button>
                    <h2 style={{ margin: 0 }}>Initiating Workflow: {processingLead.customer_name}</h2>
                </div>
                <LeadForm 
                    leadData={processingLead} 
                    onLeadCreated={() => { setProcessingLead(null); fetchLeads(); }} 
                />
            </div>
        );
    }

    return (
        <div className="container" style={{ padding: '20px' }}>
            <header style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '30px' }}>
                <motion.div initial={{ opacity: 0, x: -20 }} animate={{ opacity: 1, x: 0 }}>
                    <h1 style={{ fontSize: '2.5rem', fontWeight: '900', margin: 0, background: 'linear-gradient(45deg, var(--primary), var(--accent))', WebkitBackgroundClip: 'text', WebkitTextFillColor: 'transparent' }}>
                        Lead Control
                    </h1>
                    <p style={{ color: 'var(--text-muted)', fontSize: '1.1rem' }}>Transparent Credit Appraisal & Performance Workflow</p>
                </motion.div>
                
                <motion.button 
                    whileHover={{ scale: 1.05 }}
                    whileTap={{ scale: 0.95 }}
                    className={`btn ${showForm || editingLead ? 'btn-secondary' : 'btn-primary'}`} 
                    onClick={() => { setShowForm(!showForm); setEditingLead(null); }}
                    style={{ fontSize: '1rem', padding: '12px 25px', display: 'flex', alignItems: 'center', gap: '10px', borderRadius: '12px' }}
                >
                    {showForm || editingLead ? <><List size={20} /> View All Leads</> : <><Plus size={20} /> Generate New Lead</>}
                </motion.button>
            </header>

            <AnimatePresence mode="wait">
                {(showForm || editingLead) ? (
                    <motion.div 
                        key="form"
                        initial={{ opacity: 0, y: 30 }}
                        animate={{ opacity: 1, y: 0 }}
                        exit={{ opacity: 0, y: -30 }}
                    >
                        <InitialLeadForm 
                            existingLead={editingLead} 
                            onLeadCreated={() => { setShowForm(false); setEditingLead(null); fetchLeads(); }} 
                        />
                    </motion.div>
                ) : (
                    <motion.div 
                        key="table"
                        initial={{ opacity: 0 }}
                        animate={{ opacity: 1 }}
                    >
                        {/* Tabs & Search */}
                        <div style={{ marginBottom: '25px', display: 'flex', justifyContent: 'space-between', alignItems: 'center', gap: '20px', flexWrap: 'wrap' }}>
                            <div style={{ display: 'flex', gap: '10px', background: 'rgba(255,255,255,0.5)', padding: '5px', borderRadius: '15px' }}>
                                {['Active', 'Draft', 'Converted', 'Rejected'].map(tab => (
                                    <button 
                                        key={tab}
                                        onClick={() => setActiveTab(tab)}
                                        style={{ 
                                            padding: '8px 20px', borderRadius: '10px', border: 'none', 
                                            background: activeTab === tab ? 'white' : 'transparent',
                                            color: activeTab === tab ? 'var(--primary)' : 'var(--text-muted)',
                                            fontWeight: '700', cursor: 'pointer', transition: 'all 0.3s',
                                            boxShadow: activeTab === tab ? '0 4px 10px rgba(0,0,0,0.05)' : 'none'
                                        }}
                                    >
                                        {tab}
                                    </button>
                                ))}
                            </div>

                            <div style={{ position: 'relative', minWidth: '300px', display: 'flex', gap: '15px' }}>
                                <div style={{ position: 'relative', flex: 1 }}>
                                    <Search size={18} style={{ position: 'absolute', left: '15px', top: '50%', transform: 'translateY(-50%)', color: 'var(--text-muted)' }} />
                                    <input 
                                        type="text" placeholder="Search by name or ID..." 
                                        style={{ paddingLeft: '45px', width: '100%', background: 'white', border: 'none', borderRadius: '12px', height: '45px', boxShadow: '0 4px 15px rgba(0,0,0,0.05)' }}
                                        value={searchTerm} onChange={(e) => setSearchTerm(e.target.value)}
                                    />
                                </div>
                                <div style={{ display: 'flex', background: 'rgba(255,255,255,0.5)', padding: '5px', borderRadius: '12px', gap: '5px' }}>
                                    <button 
                                        onClick={() => setViewMode('grid')}
                                        style={{ 
                                            padding: '8px', borderRadius: '8px', border: 'none', 
                                            background: viewMode === 'grid' ? 'white' : 'transparent',
                                            color: viewMode === 'grid' ? 'var(--primary)' : 'var(--text-muted)',
                                            cursor: 'pointer', display: 'flex', alignItems: 'center', transition: 'all 0.3s'
                                        }}
                                        title="Grid View"
                                    >
                                        <LayoutGrid size={20} />
                                    </button>
                                    <button 
                                        onClick={() => setViewMode('table')}
                                        style={{ 
                                            padding: '8px', borderRadius: '8px', border: 'none', 
                                            background: viewMode === 'table' ? 'white' : 'transparent',
                                            color: viewMode === 'table' ? 'var(--primary)' : 'var(--text-muted)',
                                            cursor: 'pointer', display: 'flex', alignItems: 'center', transition: 'all 0.3s'
                                        }}
                                        title="Table View"
                                    >
                                        <List size={20} />
                                    </button>
                                </div>
                            </div>
                        </div>

                        {/* Conditional Rendering: Grid vs Table */}
                        {viewMode === 'grid' ? (
                            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(350px, 1fr))', gap: '20px' }}>
                                {filteredLeads.map((l, idx) => {
                                    const style = getStatusStyle(l.status);
                                    return (
                                        <motion.div 
                                            key={l.id}
                                            initial={{ opacity: 0, scale: 0.9 }}
                                            animate={{ opacity: 1, scale: 1 }}
                                            transition={{ delay: idx * 0.05 }}
                                            whileHover={{ y: -5 }}
                                            className="glass-card"
                                            style={{ padding: '20px', cursor: 'pointer', position: 'relative', overflow: 'hidden' }}
                                            onClick={() => navigate(`/leads/${l.id}`)}
                                        >
                                            <div style={{ 
                                                position: 'absolute', top: 0, right: 0, 
                                                width: '100px', height: '100px', 
                                                background: `radial-gradient(circle at top right, ${style.bg}80, transparent)`,
                                                zIndex: 0
                                            }} />
                                            
                                            <div style={{ position: 'relative', zIndex: 1 }}>
                                                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '15px' }}>
                                                    <span style={{ fontSize: '0.8rem', fontWeight: '800', color: 'var(--primary)', letterSpacing: '1px' }}>{l.lead_id}</span>
                                                    <div style={{ display: 'flex', gap: '8px' }}>
                                                        <span style={{ 
                                                            padding: '4px 10px', borderRadius: '20px', fontSize: '0.65rem', fontWeight: '900',
                                                            background: l.risk_category === 'Low' ? '#dcfce7' : l.risk_category === 'High' ? '#fee2e2' : '#fef3c7',
                                                            color: l.risk_category === 'Low' ? '#166534' : l.risk_category === 'High' ? '#b91c1c' : '#92400e'
                                                        }}>
                                                            {l.risk_category || 'Moderate'} Risk
                                                        </span>
                                                        <span style={{ 
                                                            padding: '4px 12px', borderRadius: '20px', fontSize: '0.65rem', fontWeight: '900',
                                                            background: style.bg, color: style.color, textTransform: 'uppercase'
                                                        }}>
                                                            {l.status}
                                                        </span>
                                                    </div>
                                                </div>

                                                <h3 style={{ fontSize: '1.25rem', fontWeight: '800', margin: '0 0 5px 0' }}>{l.customer_name}</h3>
                                                <p style={{ color: 'var(--text-muted)', fontSize: '0.85rem', marginBottom: '15px' }}>{l.loan_type} ({l.customer_type})</p>

                                                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-end', marginTop: '20px' }}>
                                                    <div>
                                                        <p style={{ fontSize: '0.7rem', color: 'var(--text-muted)', margin: 0 }}>Qualification Score</p>
                                                        <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                                                            <h4 style={{ fontSize: '1.5rem', margin: 0, fontWeight: '800', color: 'var(--primary)' }}>{l.lqs_score || 0}</h4>
                                                            <span style={{ fontSize: '0.8rem', color: 'var(--text-muted)' }}>/ 100</span>
                                                        </div>
                                                    </div>
                                                    <div style={{ textAlign: 'right' }}>
                                                        <p style={{ fontSize: '0.7rem', color: 'var(--text-muted)', margin: 0 }}>Proposed Limit</p>
                                                        <p style={{ fontSize: '1.1rem', fontWeight: '800', margin: 0 }}>रु {parseFloat(l.proposed_limit || 0).toLocaleString()}</p>
                                                    </div>
                                                </div>

                                                <div style={{ marginTop: '15px', paddingTop: '15px', borderTop: '1px solid var(--glass-border)', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                                                    <div style={{ display: 'flex', alignItems: 'center', gap: '6px', fontSize: '0.75rem', color: 'var(--text-muted)' }}>
                                                        <User size={14} />
                                                        <span>{l.initiator_name || 'Staff Member'}</span>
                                                    </div>
                                                    <div style={{ display: 'flex', alignItems: 'center', gap: '4px', fontSize: '0.75rem', color: 'var(--primary)', fontWeight: '700' }}>
                                                        Details <ChevronRight size={14} />
                                                    </div>
                                                </div>
                                            </div>
                                        </motion.div>
                                    );
                                })}
                            </div>
                        ) : (
                            <div className="glass-card" style={{ padding: '0', overflowX: 'auto', borderRadius: '15px' }}>
                                <table style={{ width: '100%', borderCollapse: 'collapse', textAlign: 'left', minWidth: '1000px' }}>
                                    <thead style={{ background: 'rgba(0,0,0,0.02)', borderBottom: '1px solid var(--glass-border)' }}>
                                        <tr>
                                            <th style={{ padding: '15px 20px', fontSize: '0.85rem', fontWeight: '800' }}>Lead ID</th>
                                            <th style={{ padding: '15px 20px', fontSize: '0.85rem', fontWeight: '800' }}>Customer</th>
                                            <th style={{ padding: '15px 20px', fontSize: '0.85rem', fontWeight: '800' }}>Loan Type</th>
                                            <th style={{ padding: '15px 20px', fontSize: '0.85rem', fontWeight: '800' }}>Proposed Limit</th>
                                            <th style={{ padding: '15px 20px', fontSize: '0.85rem', fontWeight: '800' }}>Score</th>
                                            <th style={{ padding: '15px 20px', fontSize: '0.85rem', fontWeight: '800' }}>Risk</th>
                                            <th style={{ padding: '15px 20px', fontSize: '0.85rem', fontWeight: '800' }}>Status</th>
                                            <th style={{ padding: '15px 20px', fontSize: '0.85rem', fontWeight: '800' }}>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {filteredLeads.map((l, idx) => {
                                            const statusStyle = getStatusStyle(l.status);
                                            return (
                                                <motion.tr 
                                                    key={l.id}
                                                    initial={{ opacity: 0, x: -10 }}
                                                    animate={{ opacity: 1, x: 0 }}
                                                    transition={{ delay: idx * 0.03 }}
                                                    style={{ borderBottom: '1px solid var(--glass-border)', cursor: 'pointer' }}
                                                    onClick={() => navigate(`/leads/${l.id}`)}
                                                    whileHover={{ background: 'rgba(0,0,0,0.01)' }}
                                                >
                                                    <td style={{ padding: '15px 20px' }}>
                                                        <span style={{ fontSize: '0.8rem', fontWeight: '700', color: 'var(--primary)' }}>{l.lead_id}</span>
                                                    </td>
                                                    <td style={{ padding: '15px 20px' }}>
                                                        <div style={{ fontWeight: '700' }}>{l.customer_name}</div>
                                                        <div style={{ fontSize: '0.75rem', color: 'var(--text-muted)' }}>{l.customer_type}</div>
                                                    </td>
                                                    <td style={{ padding: '15px 20px', fontSize: '0.9rem' }}>{l.loan_type}</td>
                                                    <td style={{ padding: '15px 20px', fontWeight: '700' }}>रु {parseFloat(l.proposed_limit || 0).toLocaleString()}</td>
                                                    <td style={{ padding: '15px 20px' }}>
                                                        <div style={{ display: 'flex', alignItems: 'center', gap: '5px' }}>
                                                            <span style={{ fontWeight: '800', color: 'var(--primary)' }}>{l.lqs_score || 0}</span>
                                                            <span style={{ fontSize: '0.7rem', color: 'var(--text-muted)' }}>/ 100</span>
                                                        </div>
                                                    </td>
                                                    <td style={{ padding: '15px 20px' }}>
                                                        <span style={{ 
                                                            padding: '4px 10px', borderRadius: '20px', fontSize: '0.65rem', fontWeight: '800',
                                                            background: l.risk_category === 'Low' ? '#dcfce7' : l.risk_category === 'High' ? '#fee2e2' : '#fef3c7',
                                                            color: l.risk_category === 'Low' ? '#166534' : l.risk_category === 'High' ? '#b91c1c' : '#92400e'
                                                        }}>
                                                            {l.risk_category || 'Moderate'}
                                                        </span>
                                                    </td>
                                                    <td style={{ padding: '15px 20px' }}>
                                                        <span style={{ 
                                                            padding: '4px 12px', borderRadius: '20px', fontSize: '0.65rem', fontWeight: '800',
                                                            background: statusStyle.bg, color: statusStyle.color
                                                        }}>
                                                            {l.status}
                                                        </span>
                                                    </td>
                                                    <td style={{ padding: '15px 20px' }}>
                                                        <button className="btn btn-secondary" style={{ padding: '5px 10px', fontSize: '0.8rem', borderRadius: '8px' }}>
                                                            <Eye size={14} />
                                                        </button>
                                                    </td>
                                                </motion.tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        )}

                        {filteredLeads.length === 0 && (
                            <div style={{ textAlign: 'center', padding: '100px 0', color: 'var(--text-muted)' }}>
                                <Layers size={48} style={{ opacity: 0.3, marginBottom: '20px' }} />
                                <p>No leads found in this category.</p>
                            </div>
                        )}
                    </motion.div>
                )}
            </AnimatePresence>
        </div>
    );
};

export default LeadsPage;
