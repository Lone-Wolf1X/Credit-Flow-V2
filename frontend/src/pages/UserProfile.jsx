import React from 'react';
import { useAuth } from '../context/AuthContext';
import { User, Shield, Zap, Target, Lock, MapPin, Building, Send, ChevronRight } from 'lucide-react';
import api from '../api';
import { motion, AnimatePresence } from 'framer-motion';

const UserProfile = () => {
    const { user } = useAuth();
    const [showTransferModal, setShowTransferModal] = React.useState(false);
    const [branches, setBranches] = React.useState([]);
    const [provinces, setProvinces] = React.useState([]);
    const [transferData, setTransferData] = React.useState({ target_branch_id: '', target_province_id: '', reason: '' });
    const [submitting, setSubmitting] = React.useState(false);

    React.useEffect(() => {
        if (showTransferModal) {
            fetchLocations();
        }
    }, [showTransferModal]);

    const fetchLocations = async () => {
        try {
            const [bRes, pRes] = await Promise.all([
                api.get('/branches'),
                api.get('/users/provinces')
            ]);
            setBranches(bRes.data);
            setProvinces(pRes.data);
        } catch (err) {
            console.error('Error fetching locations');
        }
    };

    const handleTransferSubmit = async (e) => {
        e.preventDefault();
        setSubmitting(true);
        try {
            await api.post('/users/transfer-request', transferData);
            alert('Transfer request submitted successfully!');
            setShowTransferModal(false);
            setTransferData({ target_branch_id: '', target_province_id: '', reason: '' });
        } catch (err) {
            alert('Failed to submit request');
        } finally {
            setSubmitting(false);
        }
    };

    if (!user) return <div className="container" style={{ padding: '40px', textAlign: 'center' }}>Loading Profile...</div>;

    return (
        <div className="container" style={{ padding: '20px' }}>
            <div className="glass-card" style={{ maxWidth: '900px', margin: '0 auto', overflow: 'hidden', padding: 0 }}>
                <div style={{ height: '120px', background: 'linear-gradient(to right, var(--primary), var(--accent))' }}></div>
                <div style={{ padding: '0 40px 40px 40px', marginTop: '-50px' }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-end', marginBottom: '30px' }}>
                        <div style={{ 
                            width: '120px', height: '120px', borderRadius: '30px', background: 'white', 
                            display: 'flex', alignItems: 'center', justifyContent: 'center', 
                            boxShadow: '0 10px 25px rgba(0,0,0,0.1)', border: '5px solid white'
                        }}>
                            <User size={60} className="text-primary" />
                        </div>
                        <div style={{ textAlign: 'right' }}>
                            <span style={{ 
                                padding: '6px 15px', borderRadius: '20px', background: 'var(--secondary)', 
                                color: 'var(--primary)', fontWeight: '800', fontSize: '0.75rem', textTransform: 'uppercase' 
                            }}>
                                {user.role}
                            </span>
                        </div>
                    </div>

                    <div style={{ display: 'flex', flexWrap: 'wrap', gap: '40px' }}>
                        <div style={{ flex: 1, minWidth: '300px' }}>
                            <h1 style={{ fontSize: '2rem', margin: '0 0 5px 0' }}>{user.name}</h1>
                            <p style={{ color: 'var(--text-muted)', fontSize: '1.1rem', marginBottom: '25px' }}>{user.designation}</p>
                            
                             <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '20px' }}>
                                <div style={{ background: 'var(--bg-main)', padding: '20px', borderRadius: '15px', border: '1px solid var(--glass-border)' }}>
                                    <div style={{ color: 'var(--text-muted)', fontSize: '0.8rem', marginBottom: '5px', textTransform: 'uppercase' }}>Staff ID</div>
                                    <div style={{ fontWeight: '700', fontSize: '1.1rem' }}>{user.staff_id}</div>
                                </div>
                                <div style={{ background: 'var(--bg-main)', padding: '20px', borderRadius: '15px', border: '1px solid var(--glass-border)' }}>
                                    <div style={{ color: 'var(--text-muted)', fontSize: '0.8rem', marginBottom: '5px', textTransform: 'uppercase' }}>SOL ID</div>
                                    <div style={{ fontWeight: '700', fontSize: '1.1rem' }}>{user.sol_id || 'N/A'}</div>
                                </div>
                                <div style={{ background: 'var(--bg-main)', padding: '20px', borderRadius: '15px', border: '1px solid var(--glass-border)' }}>
                                    <div style={{ color: 'var(--text-muted)', fontSize: '0.8rem', marginBottom: '5px', textTransform: 'uppercase' }}>Branch</div>
                                    <div style={{ fontWeight: '700', fontSize: '1rem' }}>{user.branch_name || 'N/A'}</div>
                                </div>
                                <div style={{ background: 'var(--bg-main)', padding: '20px', borderRadius: '15px', border: '1px solid var(--glass-border)' }}>
                                    <div style={{ color: 'var(--text-muted)', fontSize: '0.8rem', marginBottom: '5px', textTransform: 'uppercase' }}>Province</div>
                                    <div style={{ fontWeight: '700', fontSize: '1rem' }}>{user.province_name || 'N/A'}</div>
                                </div>
                            </div>
                            
                            <button 
                                onClick={() => setShowTransferModal(true)}
                                className="btn"
                                style={{ 
                                    marginTop: '30px', background: 'var(--primary)', color: 'white', 
                                    padding: '15px 30px', borderRadius: '15px', width: '100%',
                                    display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '10px',
                                    fontWeight: '700'
                                }}
                            >
                                <Send size={18} /> Request Branch Transfer
                            </button>
                        </div>

                        <div style={{ flex: 1, minWidth: '300px', display: 'flex', flexDirection: 'column', gap: '20px' }}>
                            <div style={{ 
                                background: 'white', border: '2px solid var(--accent)', padding: '25px', borderRadius: '20px',
                                position: 'relative', overflow: 'hidden'
                            }}>
                                <Zap size={80} style={{ position: 'absolute', right: '-10px', top: '-10px', opacity: 0.05, color: 'var(--accent)' }} />
                                <div style={{ display: 'flex', alignItems: 'center', gap: '10px', color: 'var(--accent)', fontWeight: '800', textTransform: 'uppercase', fontSize: '0.8rem', marginBottom: '10px' }}>
                                    <Shield size={16} /> Asset Power Authority
                                </div>
                                <div style={{ fontSize: '1.8rem', fontWeight: '900', color: 'var(--primary)' }}>
                                    रु {parseFloat(user.limit_power || 0).toLocaleString()}
                                </div>
                                <div style={{ fontSize: '0.8rem', color: 'var(--text-muted)', marginTop: '5px' }}>
                                    Individual processing limit for single asset lead.
                                </div>
                            </div>

                            <div style={{ display: 'flex', gap: '10px' }}>
                                <div style={{ flex: 1, background: '#f8fafc', padding: '15px', borderRadius: '15px', textAlign: 'center' }}>
                                    <Target size={20} className="text-primary" style={{ margin: '0 auto 5px' }} />
                                    <div style={{ fontSize: '0.7rem', color: '#64748b' }}>CONFIDENCE</div>
                                    <div style={{ fontWeight: '800' }}>HIGH</div>
                                </div>
                                <div style={{ flex: 1, background: '#f8fafc', padding: '15px', borderRadius: '15px', textAlign: 'center' }}>
                                    <Lock size={20} className="text-secondary" style={{ margin: '0 auto 5px' }} />
                                    <div style={{ fontSize: '0.7rem', color: '#64748b' }}>ENCRYPTION</div>
                                    <div style={{ fontWeight: '800' }}>AES-256</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <AnimatePresence>
                {showTransferModal && (
                    <motion.div 
                        initial={{ opacity: 0 }}
                        animate={{ opacity: 1 }}
                        exit={{ opacity: 0 }}
                        style={{ 
                            position: 'fixed', top: 0, left: 0, right: 0, bottom: 0, 
                            background: 'rgba(0,0,0,0.5)', display: 'flex', alignItems: 'center', 
                            justifyContent: 'center', zIndex: 1000, padding: '20px'
                        }}
                    >
                        <motion.div 
                            initial={{ scale: 0.9, y: 20 }}
                            animate={{ scale: 1, y: 0 }}
                            exit={{ scale: 0.9, y: 20 }}
                            className="glass-card"
                            style={{ maxWidth: '500px', width: '100%', padding: '30px' }}
                        >
                            <h2 style={{ marginBottom: '20px', display: 'flex', alignItems: 'center', gap: '10px' }}>
                                <Building size={24} className="text-primary" />
                                Branch Transfer Request
                            </h2>
                            <form onSubmit={handleTransferSubmit}>
                                <div className="form-group" style={{ marginBottom: '20px' }}>
                                    <label>Target Province</label>
                                    <select 
                                        value={transferData.target_province_id} 
                                        onChange={(e) => setTransferData({...transferData, target_province_id: e.target.value})}
                                        style={{ width: '100%', padding: '12px', borderRadius: '10px', border: '1px solid var(--glass-border)' }}
                                        required
                                    >
                                        <option value="">Select Province</option>
                                        {provinces.map(p => <option key={p.id} value={p.id}>{p.name}</option>)}
                                    </select>
                                </div>
                                <div className="form-group" style={{ marginBottom: '20px' }}>
                                    <label>Target Branch</label>
                                    <select 
                                        value={transferData.target_branch_id} 
                                        onChange={(e) => setTransferData({...transferData, target_branch_id: e.target.value})}
                                        style={{ width: '100%', padding: '12px', borderRadius: '10px', border: '1px solid var(--glass-border)' }}
                                        required
                                    >
                                        <option value="">Select Branch</option>
                                        {branches.filter(b => !transferData.target_province_id || b.province === provinces.find(p => p.id == transferData.target_province_id)?.name).map(b => (
                                            <option key={b.id} value={b.id}>{b.name} ({b.sol_id})</option>
                                        ))}
                                    </select>
                                </div>
                                <div className="form-group" style={{ marginBottom: '25px' }}>
                                    <label>Reason for Transfer</label>
                                    <textarea 
                                        rows="4"
                                        value={transferData.reason}
                                        onChange={(e) => setTransferData({...transferData, reason: e.target.value})}
                                        placeholder="Briefly explain why you are requesting a transfer..."
                                        style={{ width: '100%', padding: '12px', borderRadius: '10px', border: '1px solid var(--glass-border)' }}
                                        required
                                    ></textarea>
                                </div>
                                <div style={{ display: 'flex', gap: '10px' }}>
                                    <button type="button" className="btn btn-secondary" style={{ flex: 1 }} onClick={() => setShowTransferModal(false)}>Cancel</button>
                                    <button type="submit" className="btn btn-primary" style={{ flex: 2 }} disabled={submitting}>
                                        {submitting ? 'Submitting...' : 'Submit Request'}
                                    </button>
                                </div>
                            </form>
                        </motion.div>
                    </motion.div>
                )}
            </AnimatePresence>
        </div>
    );
};

export default UserProfile;
