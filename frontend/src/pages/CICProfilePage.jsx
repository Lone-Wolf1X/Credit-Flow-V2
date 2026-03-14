import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import api from '../api';
import { 
    User, Shield, CheckCircle, Clock, AlertCircle, 
    ArrowLeft, FileText, Download, Building, 
    MapPin, Phone, Mail, Briefcase, Calendar, Info
} from 'lucide-react';
import { motion } from 'framer-motion';
import { toast } from 'react-hot-toast';

const CICProfilePage = () => {
    const { type, id } = useParams();
    const navigate = useNavigate();
    const [profile, setProfile] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetchProfile();
    }, [type, id]);

    const fetchProfile = async () => {
        try {
            setLoading(true);
            const res = await api.get(`/cics/profiles/${type}/${id}`);
            setProfile(res.data);
        } catch (err) {
            console.error('Error fetching profile:', err);
            toast.error('Failed to load subject profile');
        } finally {
            setLoading(false);
        }
    };

    if (loading) return (
        <div style={{ textAlign: 'center', padding: '100px 0' }}>
            <div className="loader"></div>
            <p style={{ marginTop: '20px', color: 'var(--text-muted)' }}>Loading Profile...</p>
        </div>
    );

    if (!profile) return (
        <div style={{ textAlign: 'center', padding: '100px 0' }}>
            <AlertCircle size={48} color="var(--danger)" style={{ marginBottom: '20px' }} />
            <h3>Profile Not Found</h3>
            <button className="btn btn-primary" onClick={() => navigate('/cic')}>Back to Dashboard</button>
        </div>
    );

    return (
        <div className="container" style={{ padding: '20px' }}>
            <motion.div initial={{ opacity: 0, x: -20 }} animate={{ opacity: 1, x: 0 }} style={{ marginBottom: '24px' }}>
                <button onClick={() => navigate('/cic')} className="btn" style={{ background: 'white', border: '1px solid var(--glass-border)', display: 'flex', alignItems: 'center', gap: '8px', fontWeight: '700' }}>
                    <ArrowLeft size={18} /> Back to Dashboard
                </button>
            </motion.div>

            <header style={{ marginBottom: '30px' }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: '20px' }}>
                    <div style={{ 
                        width: '80px', height: '80px', borderRadius: '20px', 
                        background: 'linear-gradient(45deg, var(--primary), var(--accent))',
                        display: 'flex', alignItems: 'center', justifyContent: 'center', color: 'white'
                    }}>
                        {type === 'Personal' ? <User size={40} /> : <Building size={40} />}
                    </div>
                    <div>
                        <h1 style={{ margin: 0, fontSize: '2rem', fontWeight: '900' }}>
                            {type === 'Personal' ? profile.full_name : profile.business_name}
                        </h1>
                        <p style={{ margin: 0, color: 'var(--text-muted)', fontWeight: '600' }}>
                            {type} Subject Profile | {profile.pan_number || profile.pan_vat_number || 'No PAN/VAT'}
                        </p>
                    </div>
                </div>
            </header>

            <div className="grid-3" style={{ marginBottom: '30px' }}>
                {/* Section 1: Identity / Registration */}
                <div className="glass-card" style={{ padding: '25px', borderTop: '5px solid var(--primary)' }}>
                    <h3 style={{ margin: '0 0 20px 0', fontSize: '1.1rem', display: 'flex', alignItems: 'center', gap: '10px' }}>
                        <Shield size={20} className="text-primary" /> Identity Details
                    </h3>
                    <div style={{ display: 'flex', flexDirection: 'column', gap: '15px' }}>
                        {type === 'Personal' ? (
                            <>
                                <InfoItem label="Citizenship No." value={profile.citizenship_number} icon={<FileText size={16} />} />
                                <InfoItem label="Issued District" value={profile.citizenship_issued_district} icon={<MapPin size={16} />} />
                                <InfoItem label="DOB" value={profile.date_of_birth ? new Date(profile.date_of_birth).toLocaleDateString() : 'N/A'} icon={<Calendar size={16} />} />
                                <InfoItem label="Father's Name" value={profile.father_name} />
                            </>
                        ) : (
                            <>
                                <InfoItem label="Reg. Number" value={profile.registration_number} icon={<FileText size={16} />} />
                                <InfoItem label="Reg. Date" value={profile.registration_date ? new Date(profile.registration_date).toLocaleDateString() : 'N/A'} icon={<Calendar size={16} />} />
                                <InfoItem label="Business Type" value={profile.business_type} icon={<Briefcase size={16} />} />
                                <InfoItem label="PAN/VAT" value={profile.pan_vat_number} />
                            </>
                        )}
                    </div>
                </div>

                {/* Section 2: Contact & Address */}
                <div className="glass-card" style={{ padding: '25px', borderTop: '5px solid var(--accent)' }}>
                    <h3 style={{ margin: '0 0 20px 0', fontSize: '1.1rem', display: 'flex', alignItems: 'center', gap: '10px' }}>
                        <MapPin size={20} className="text-accent" /> Contact Info
                    </h3>
                    <div style={{ display: 'flex', flexDirection: 'column', gap: '15px' }}>
                        <InfoItem label="Phone" value={profile.contact_number} icon={<Phone size={16} />} />
                        <InfoItem label="Email" value={profile.email} icon={<Mail size={16} />} />
                        <InfoItem 
                            label="Address" 
                            value={profile.current_address || profile.registered_address || profile.operating_address} 
                            icon={<MapPin size={16} />} 
                        />
                    </div>
                </div>

                {/* Section 3: Summary & Status */}
                <div className="glass-card" style={{ padding: '25px', borderTop: '5px solid var(--secondary)' }}>
                    <h3 style={{ margin: '0 0 20px 0', fontSize: '1.1rem', display: 'flex', alignItems: 'center', gap: '10px' }}>
                        <CheckCircle size={20} className="text-secondary" /> System Linkage
                    </h3>
                    <div style={{ background: '#f8fafc', padding: '15px', borderRadius: '12px', marginBottom: '15px' }}>
                        <small style={{ color: 'var(--text-muted)', fontWeight: '700', fontSize: '0.65rem' }}>LINKED LEAD</small>
                        <div style={{ fontWeight: '800', marginTop: '4px' }}>{profile.lead_id || 'Standalone / No Lead'}</div>
                    </div>
                    <div style={{ display: 'flex', gap: '10px' }}>
                        <div style={{ flex: 1, textAlign: 'center', padding: '10px', background: '#ecfdf5', borderRadius: '10px', color: '#065f46' }}>
                            <div style={{ fontSize: '1.2rem', fontWeight: '900' }}>{profile.history?.length || 0}</div>
                            <div style={{ fontSize: '0.6rem', fontWeight: '800' }}>TOTAL CHECKS</div>
                        </div>
                        <div style={{ flex: 1, textAlign: 'center', padding: '10px', background: '#fffbeb', borderRadius: '10px', color: '#92400e' }}>
                            <div style={{ fontSize: '1.2rem', fontWeight: '900' }}>{profile.history?.filter(h => h.is_hit).length || 0}</div>
                            <div style={{ fontSize: '0.6rem', fontWeight: '800' }}>TOTAL HITS</div>
                        </div>
                    </div>
                </div>
            </div>

            <div className="glass-card" style={{ padding: '30px' }}>
                <h3 style={{ margin: '0 0 20px 0', fontWeight: '800', display: 'flex', alignItems: 'center', gap: '10px' }}>
                    <Clock size={24} className="text-primary" /> Credit Investigation History
                </h3>
                <div className="table-container">
                    <table className="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Hit Status</th>
                                <th>Report</th>
                            </tr>
                        </thead>
                        <tbody>
                            {profile.history?.map((h, idx) => (
                                <tr key={idx}>
                                    <td>{new Date(h.created_at).toLocaleDateString()}</td>
                                    <td>
                                        <span className={`badge ${h.status === 'Completed' ? 'badge-success' : 'badge-warning'}`}>
                                            {h.status}
                                        </span>
                                    </td>
                                    <td>
                                        <span style={{ 
                                            padding: '4px 12px', borderRadius: '20px', fontSize: '0.75rem', fontWeight: '800',
                                            background: h.is_hit ? '#fee2e2' : '#dcfce7',
                                            color: h.is_hit ? '#b91c1c' : '#166534'
                                        }}>
                                            {h.is_hit ? 'HIT DETECTED' : 'CLEAR / NO HIT'}
                                        </span>
                                    </td>
                                    <td>
                                        {h.report_url ? (
                                            <a href={h.report_url} target="_blank" rel="noopener noreferrer" className="action-btn" title="Download Report">
                                                <Download size={16} />
                                            </a>
                                        ) : 'N/A'}
                                    </td>
                                </tr>
                            ))}
                            {(!profile.history || profile.history.length === 0) && (
                                <tr>
                                    <td colSpan="4" style={{ textAlign: 'center', color: 'var(--text-muted)', padding: '40px' }}>
                                        No historical CIC records found.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    );
};

const InfoItem = ({ label, value, icon }) => (
    <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
        <div style={{ color: 'var(--primary)', opacity: 0.7 }}>{icon || <div style={{ width: 16 }} />}</div>
        <div>
            <small style={{ display: 'block', color: 'var(--text-muted)', fontWeight: '700', fontSize: '0.6rem', textTransform: 'uppercase' }}>{label}</small>
            <span style={{ fontWeight: '700', fontSize: '0.9rem' }}>{value || 'Not Provided'}</span>
        </div>
    </div>
);

export default CICProfilePage;
