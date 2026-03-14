import React, { useState, useEffect } from 'react';
import api from '../api';
import { useAuth } from '../context/AuthContext';
import { ShieldCheck, UserPlus, Search, Filter, AlertTriangle, CheckCircle, Clock } from 'lucide-react';
import toast from 'react-hot-toast';

const ValuatorsPage = () => {
    const [valuators, setValuators] = useState([]);
    const [branches, setBranches] = useState([]);
    const [showOnboardModal, setShowOnboardModal] = useState(false);
    const [filters, setFilters] = useState({ status: '', branch_id: '', search: '' });
    const { user } = useAuth();
    const [formData, setFormData] = useState({
        name: '', firm_name: '', license_number: '', experience_years: '', contact_number: '', email: '',
        start_date: '', expiry_date: ''
    });

    useEffect(() => {
        fetchValuators();
        fetchBranches();
    }, [filters.status, filters.branch_id]);

    const fetchBranches = async () => {
        try {
            const res = await api.get('/branches');
            setBranches(res.data);
        } catch (err) {
            console.error('Failed to fetch branches');
        }
    };

    const fetchValuators = async () => {
        try {
            const params = new URLSearchParams();
            if (filters.status) params.append('status', filters.status);
            if (filters.branch_id) params.append('branch_id', filters.branch_id);
            
            const res = await api.get(`/valuators?${params.toString()}`);
            setValuators(res.data);
        } catch (err) {
            toast.error('Failed to fetch valuators');
        }
    };

    const handleOnboard = async (e) => {
        e.preventDefault();
        try {
            await api.post('/valuators/onboard', formData);
            toast.success('Valuator onboarding request submitted!');
            setShowOnboardModal(false);
            setFormData({ 
                name: '', firm_name: '', license_number: '', experience_years: '', contact_number: '', email: '',
                start_date: '', expiry_date: ''
            });
            fetchValuators();
        } catch (err) {
            toast.error(err.response?.data?.error || 'Failed to onboard valuator');
        }
    };

    const handleStatusUpdate = async (id, status) => {
        try {
            await api.put(`/valuators/${id}/status`, { status });
            toast.success(`Valuator marked as ${status}`);
            fetchValuators();
        } catch (err) {
            toast.error('Failed to update status');
        }
    };

    return (
        <div style={{ padding: '20px' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '30px' }}>
                <div>
                    <h1 style={{ fontSize: '1.8rem', fontWeight: '800', margin: 0, display: 'flex', alignItems: 'center', gap: '15px' }}>
                        <ShieldCheck size={32} color="var(--primary)" />
                        Valuator Management
                    </h1>
                    <p style={{ color: 'var(--text-muted)', margin: '5px 0 0 0' }}>Manage registered collateral valuators and firms</p>
                </div>
                <button className="btn btn-primary" onClick={() => setShowOnboardModal(true)} style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                    <UserPlus size={20} /> Onboard New Valuator
                </button>
            </div>

            <div className="glass-card" style={{ padding: '25px' }}>
                <div style={{ display: 'flex', gap: '15px', marginBottom: '25px', flexWrap: 'wrap' }}>
                    <div style={{ flex: 1, minWidth: '200px', position: 'relative' }}>
                        <Search size={18} style={{ position: 'absolute', left: '15px', top: '50%', transform: 'translateY(-50%)', color: 'var(--text-muted)' }} />
                        <input 
                            type="text" 
                            placeholder="Search by name, firm or license..." 
                            className="form-input" 
                            style={{ paddingLeft: '45px' }}
                            value={filters.search}
                            onChange={e => setFilters({...filters, search: e.target.value})}
                        />
                    </div>
                    
                    {user?.role === 'Admin' && (
                        <select 
                            className="form-input" 
                            style={{ width: '200px' }}
                            value={filters.branch_id}
                            onChange={e => setFilters({...filters, branch_id: e.target.value})}
                        >
                            <option value="">All Branches</option>
                            {branches.map(b => (
                                <option key={b.id} value={b.id}>{b.name}</option>
                            ))}
                        </select>
                    )}

                    <select 
                        className="form-input" 
                        style={{ width: '150px' }}
                        value={filters.status}
                        onChange={e => setFilters({...filters, status: e.target.value})}
                    >
                        <option value="">All Status</option>
                        <option value="Pending">Pending</option>
                        <option value="Active">Active</option>
                        <option value="Suspended">Suspended</option>
                    </select>
                </div>

                <div className="table-container">
                    <table className="data-table">
                        <thead>
                            <tr>
                                <th>Engineer Name</th>
                                <th>Firm / License</th>
                                <th>Validity Period</th>
                                <th>Experience</th>
                                <th>Status</th>
                                <th>Branch</th>
                                <th style={{ textAlign: 'right' }}>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {valuators.map(v => (
                                <tr key={v.id}>
                                    <td>
                                        <div style={{ fontWeight: '700' }}>{v.name}</div>
                                        <div style={{ fontSize: '0.8rem', color: 'var(--text-muted)' }}>{v.email} | {v.contact_number}</div>
                                    </td>
                                    <td>
                                        <div style={{ fontWeight: '600' }}>{v.firm_name}</div>
                                        <div style={{ fontSize: '0.75rem', color: 'var(--primary)', fontWeight: '700' }}>{v.license_number}</div>
                                    </td>
                                    <td>
                                        <div className="text-sm">
                                            <span className="text-xs color-muted">From:</span> {v.start_date ? new Date(v.start_date).toLocaleDateString() : 'N/A'}
                                        </div>
                                        <div className="text-sm">
                                            <span className="text-xs color-muted">To:</span> {v.expiry_date ? new Date(v.expiry_date).toLocaleDateString() : 'N/A'}
                                        </div>
                                    </td>
                                    <td>{v.experience_years} Years</td>
                                    <td>
                                        <span className={`badge ${
                                            v.status === 'Active' ? 'badge-success' : 
                                            v.status === 'Pending' ? 'badge-pending' : 'badge-danger'
                                        }`}>
                                            {v.status}
                                        </span>
                                    </td>
                                    <td className="font-bold">{v.branch_name || 'N/A'}</td>
                                    <td style={{ textAlign: 'right' }}>
                                        {user?.role === 'Admin' && v.status === 'Pending' && (
                                            <button onClick={() => handleStatusUpdate(v.id, 'Active')} className="btn btn-sm btn-primary" style={{ padding: '5px 10px' }}>Approve</button>
                                        )}
                                        {user?.role === 'Admin' && v.status === 'Active' && (
                                            <button onClick={() => handleStatusUpdate(v.id, 'Suspended')} className="btn btn-sm btn-danger" style={{ padding: '5px 10px' }}>Suspend</button>
                                        )}
                                        {v.status === 'Active' && (
                                            <span className="badge badge-success">Verified</span>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>

            {showOnboardModal && (
                <div className="modal-overlay">
                    <div className="glass-card modal-content" style={{ width: '600px', padding: '30px' }}>
                        <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '25px' }}>
                            <h3 style={{ margin: 0, fontWeight: '800' }}>Onboard New Valuator</h3>
                            <button onClick={() => setShowOnboardModal(false)} style={{ background: 'none', border: 'none', cursor: 'pointer' }}><AlertTriangle size={24} /></button>
                        </div>
                        <form onSubmit={handleOnboard}>
                            <div className="form-row">
                                <div className="form-group">
                                    <label>Valuator Full Name</label>
                                    <input type="text" value={formData.name} onChange={e => setFormData({...formData, name: e.target.value})} required />
                                </div>
                                <div className="form-group">
                                    <label>Firm Name</label>
                                    <input type="text" value={formData.firm_name} onChange={e => setFormData({...formData, firm_name: e.target.value})} required />
                                </div>
                            </div>
                            <div className="form-row">
                                <div className="form-group">
                                    <label>License Number</label>
                                    <input type="text" value={formData.license_number} onChange={e => setFormData({...formData, license_number: e.target.value})} required />
                                </div>
                                <div className="form-group">
                                    <label>Years of Experience</label>
                                    <input type="number" min="3" value={formData.experience_years} onChange={e => setFormData({...formData, experience_years: e.target.value})} required />
                                </div>
                            </div>
                             <div className="form-row">
                                <div className="form-group">
                                    <label>Contact Number</label>
                                    <input type="text" value={formData.contact_number} onChange={e => setFormData({...formData, contact_number: e.target.value})} required />
                                </div>
                                <div className="form-group">
                                    <label>Email Address</label>
                                    <input type="email" value={formData.email} onChange={e => setFormData({...formData, email: e.target.value})} required />
                                </div>
                            </div>
                            <div className="form-row">
                                <div className="form-group">
                                    <label>Start Date</label>
                                    <input type="date" className="form-input" value={formData.start_date} onChange={e => setFormData({...formData, start_date: e.target.value})} required />
                                </div>
                                <div className="form-group">
                                    <label>Expiry Date</label>
                                    <input type="date" className="form-input" value={formData.expiry_date} onChange={e => setFormData({...formData, expiry_date: e.target.value})} required />
                                </div>
                            </div>
                            <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '15px', marginTop: '30px' }}>
                                <button type="button" className="btn btn-secondary" onClick={() => setShowOnboardModal(false)}>Cancel</button>
                                <button type="submit" className="btn btn-primary">Submit for Approval</button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
};

export default ValuatorsPage;
