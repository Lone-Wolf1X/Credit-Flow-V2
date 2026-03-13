import React, { useState, useEffect } from 'react';
import api from '../api';

const UserManagement = () => {
    const [users, setUsers] = useState([]);
    const [branches, setBranches] = useState([]);
    const [designations, setDesignations] = useState([]);
    const [provinces, setProvinces] = useState([]);
    const [formData, setFormData] = useState({ name: '', email: '', staff_id: '', role: 'Staff', branch_id: '', designation: '', province_id: '', limit_power: 0 });
    const [isEditing, setIsEditing] = useState(false);
    const [editId, setEditId] = useState(null);
    const [isTransferModalOpen, setTransferModalOpen] = useState(false);
    const [transferData, setTransferData] = useState({ user_id: '', branch_id: '', type: 'permanent', end_date: '' });
    const [transferUser, setTransferUser] = useState(null);
    const [isResetModalOpen, setResetModalOpen] = useState(false);
    const [resetData, setResetData] = useState({ type: 'manual', newPassword: '' });
    const [resetUser, setResetUser] = useState(null);

    useEffect(() => {
        fetchUsers();
        fetchBranches();
        fetchDesignations();
        fetchProvinces();
    }, []);

    const fetchProvinces = async () => {
        try {
            const res = await api.get('/users/provinces');
            setProvinces(res.data);
        } catch (err) {
            console.error('Error fetching provinces');
        }
    };

    const fetchUsers = async () => {
        const res = await api.get('/users');
        setUsers(res.data);
    };

    const fetchBranches = async () => {
        const res = await api.get('/branches');
        setBranches(res.data);
    };

    const fetchDesignations = async () => {
        try {
            const res = await api.get('/users/designations');
            setDesignations(res.data);
        } catch (err) {
            console.error('Error fetching designations');
        }
    };

    const handleChange = (e) => setFormData({ ...formData, [e.target.name]: e.target.value });

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            if (isEditing) {
                await api.put(`/users/${editId}`, formData);
            } else {
                await api.post('/auth/register', { ...formData, password: 'password123' }); // Default password
            }
            handleCancel();
            fetchUsers();
            alert('Success!');
        } catch (err) {
            alert('Operation failed');
        }
    };

    const handleCancel = () => {
        setFormData({ name: '', email: '', staff_id: '', role: 'Staff', branch_id: '', designation: '', province_id: '', limit_power: 0 });
        setIsEditing(false);
        setEditId(null);
    };

    const handleEdit = (user) => {
        setFormData(user);
        setIsEditing(true);
        setEditId(user.id);
    };

    const handleDelete = async (id) => {
        if (window.confirm('Are you sure?')) {
            await api.delete(`/users/${id}`);
            fetchUsers();
        }
    };

    const handleTransferClick = (user) => {
        setTransferUser(user);
        setTransferData({ ...transferData, user_id: user.id });
        setTransferModalOpen(true);
    };

    const handleTransferSubmit = async (e) => {
        e.preventDefault();
        try {
            await api.post(`/users/${transferUser.id}/transfer`, transferData);
            setTransferModalOpen(false);
            fetchUsers();
            alert('Transfer successful!');
        } catch (err) {
            alert('Transfer failed');
        }
    };

    const handleResetClick = (user) => {
        setResetUser(user);
        setResetData({ type: 'manual', newPassword: '' });
        setResetModalOpen(true);
    };

    const handleResetSubmit = async (e) => {
        e.preventDefault();
        try {
            const endpoint = resetData.type === 'manual' 
                ? `/users/${resetUser.id}/reset-password-manual` 
                : `/users/${resetUser.id}/reset-password-email`;
            
            await api.post(endpoint, { newPassword: resetData.newPassword });
            setResetModalOpen(false);
            alert('Password reset successful!');
        } catch (err) {
            alert('Reset failed');
        }
    };

    return (
        <div className="glass-card">
            <h3>User Management</h3>
            <form onSubmit={handleSubmit} style={{ marginTop: '20px' }}>
                <div className="form-row">
                    <div className="form-group">
                        <label>Full Name</label>
                        <input name="name" value={formData.name} onChange={handleChange} required />
                    </div>
                    <div className="form-group">
                        <label>Email</label>
                        <input name="email" value={formData.email} onChange={handleChange} required />
                    </div>
                    <div className="form-group">
                        <label>Staff ID</label>
                        <input name="staff_id" value={formData.staff_id} onChange={handleChange} required />
                    </div>
                    <div className="form-group">
                        <label>Designation</label>
                        <select name="designation" value={formData.designation} onChange={handleChange} required>
                            <option value="">Select Designation</option>
                            {designations.map(d => (
                                <option key={d.id} value={d.name}>{d.name}</option>
                            ))}
                        </select>
                    </div>
                    <div className="form-group">
                        <label>Power Limit (रु)</label>
                        <input type="number" name="limit_power" value={formData.limit_power} onChange={handleChange} />
                    </div>
                </div>
                <div className="form-row">
                    <div className="form-group">
                        <label>Role</label>
                        <select name="role" value={formData.role} onChange={handleChange}>
                            <option value="Admin">Admin</option>
                            <option value="Staff">Staff</option>
                        </select>
                    </div>
                    <div className="form-group">
                        <label>Province</label>
                        <select name="province_id" value={formData.province_id} onChange={handleChange} required>
                            <option value="">Select Province</option>
                            {provinces.map(p => (
                                <option key={p.id} value={p.id}>{p.name}</option>
                            ))}
                        </select>
                    </div>
                    <div className="form-group">
                        <label>Branch (SOL ID)</label>
                        <select name="branch_id" value={formData.branch_id} onChange={handleChange} required>
                            <option value="">Select Branch</option>
                            {branches.map(b => (
                                <option key={b.id} value={b.id}>{b.name} ({b.sol_id})</option>
                            ))}
                        </select>
                    </div>
                    <div className="form-group" style={{ display: 'flex', alignItems: 'flex-end', gap: '10px' }}>
                        <button type="submit" className="btn btn-primary" style={{ flex: 1 }}>
                            {isEditing ? 'Update User' : 'Add User'}
                        </button>
                        {isEditing && (
                            <button type="button" onClick={handleCancel} className="btn" style={{ background: 'var(--text-muted)', color: 'white' }}>
                                Cancel
                            </button>
                        )}
                    </div>
                </div>
            </form>

            <table style={{ width: '100%', marginTop: '30px', borderCollapse: 'collapse' }}>
                <thead>
                    <tr style={{ borderBottom: '1px solid var(--glass-border)', textAlign: 'left' }}>
                        <th style={{ padding: '10px' }}>Name</th>
                        <th style={{ padding: '10px' }}>Staff ID</th>
                        <th style={{ padding: '10px' }}>Province</th>
                        <th style={{ padding: '10px' }}>Branch</th>
                        <th style={{ padding: '10px' }}>Designation</th>
                        <th style={{ padding: '10px' }}>Power (रु)</th>
                        <th style={{ padding: '10px' }}>Role</th>
                        <th style={{ padding: '10px' }}>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {users.map(u => (
                        <tr key={u.id} style={{ borderBottom: '1px solid var(--glass-border)' }}>
                            <td style={{ padding: '10px' }}>{u.name}</td>
                            <td style={{ padding: '10px' }}>{u.staff_id}</td>
                            <td style={{ padding: '10px' }}>{u.province_name || 'N/A'}</td>
                            <td style={{ padding: '10px' }}>{u.branch_name || 'N/A'}</td>
                            <td style={{ padding: '10px' }}>{u.designation}</td>
                            <td style={{ padding: '10px', fontWeight: 'bold', color: 'var(--primary)' }}>
                                {u.limit_power ? parseInt(u.limit_power).toLocaleString() : '0'}
                            </td>
                            <td style={{ padding: '10px' }}>{u.role}</td>
                            <td style={{ padding: '10px' }}>
                                <button className="btn" onClick={() => handleEdit(u)} style={{ marginRight: '5px' }}>Edit</button>
                                <button className="btn" onClick={() => handleTransferClick(u)} style={{ marginRight: '5px', background: 'var(--accent)' }}>Transfer</button>
                                <button className="btn" onClick={() => handleResetClick(u)} style={{ marginRight: '5px', background: 'var(--secondary)', color: 'var(--primary)' }}>Reset Pwd</button>
                                <button className="btn" onClick={() => handleDelete(u.id)} style={{ background: 'var(--danger)' }}>Delete</button>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>

            {/* Transfer Modal */}
            {isTransferModalOpen && (
                <div style={{
                    position: 'fixed', top: 0, left: 0, width: '100%', height: '100%',
                    background: 'rgba(0,0,0,0.5)', display: 'flex', justifyContent: 'center', alignItems: 'center', zIndex: 2000
                }}>
                    <div className="glass-card" style={{ width: '450px', position: 'relative' }}>
                        <button onClick={() => setTransferModalOpen(false)} style={{ position: 'absolute', right: '15px', top: '15px', background: 'none', border: 'none', cursor: 'pointer', fontSize: '1.2rem' }}>×</button>
                        <h4>Transfer User: {transferUser?.name}</h4>
                        <form onSubmit={handleTransferSubmit} style={{ marginTop: '20px' }}>
                            <div className="form-group" style={{ marginBottom: '15px' }}>
                                <label>Target Branch</label>
                                <select 
                                    value={transferData.branch_id} 
                                    onChange={(e) => setTransferData({...transferData, branch_id: e.target.value})}
                                    required
                                >
                                    <option value="">Select Branch</option>
                                    {branches.map(b => (
                                        <option key={b.id} value={b.id}>{b.name} ({b.sol_id})</option>
                                    ))}
                                </select>
                            </div>
                            <div className="form-group" style={{ marginBottom: '15px' }}>
                                <label>Transfer Type</label>
                                <select 
                                    value={transferData.type} 
                                    onChange={(e) => setTransferData({...transferData, type: e.target.value})}
                                >
                                    <option value="permanent">Permanent</option>
                                    <option value="temporary">Temporary</option>
                                </select>
                            </div>
                            {transferData.type === 'temporary' && (
                                <div className="form-group" style={{ marginBottom: '15px' }}>
                                    <label>Assignment End Date</label>
                                    <input 
                                        type="date" 
                                        value={transferData.end_date} 
                                        onChange={(e) => setTransferData({...transferData, end_date: e.target.value})}
                                        required
                                    />
                                </div>
                            )}
                            <button type="submit" className="btn btn-primary" style={{ width: '100%', marginTop: '10px' }}>Confirm Transfer</button>
                        </form>
                    </div>
                </div>
            )}
            {/* Reset Modal */}
            {isResetModalOpen && (
                <div style={{
                    position: 'fixed', top: 0, left: 0, width: '100%', height: '100%',
                    background: 'rgba(0,0,0,0.5)', display: 'flex', justifyContent: 'center', alignItems: 'center', zIndex: 2000
                }}>
                    <div className="glass-card" style={{ width: '450px', position: 'relative' }}>
                        <button onClick={() => setResetModalOpen(false)} style={{ position: 'absolute', right: '15px', top: '15px', background: 'none', border: 'none', cursor: 'pointer', fontSize: '1.2rem' }}>×</button>
                        <h4>Reset Password: {resetUser?.name}</h4>
                        <form onSubmit={handleResetSubmit} style={{ marginTop: '20px' }}>
                            <div className="form-group" style={{ marginBottom: '15px' }}>
                                <label>Reset Method</label>
                                <select 
                                    value={resetData.type} 
                                    onChange={(e) => setResetData({...resetData, type: e.target.value})}
                                >
                                    <option value="manual">Manual Set (Instant)</option>
                                    <option value="email">Send via Email (Forced Change)</option>
                                </select>
                            </div>
                            
                            {resetData.type === 'manual' && (
                                <div className="form-group" style={{ marginBottom: '15px' }}>
                                    <label>New Password</label>
                                    <input 
                                        type="text" 
                                        value={resetData.newPassword} 
                                        onChange={(e) => setResetData({...resetData, newPassword: e.target.value})}
                                        required
                                        placeholder="Enter new password"
                                    />
                                </div>
                            )}

                            {resetData.type === 'email' && (
                                <p style={{ fontSize: '0.85rem', color: 'var(--text-muted)', marginBottom: '15px' }}>
                                    A temporary password will be generated and sent to <b>{resetUser?.email}</b>. 
                                    The user will be required to change it on their next login.
                                </p>
                            )}

                            <button type="submit" className="btn btn-primary" style={{ width: '100%', marginTop: '10px' }}>Confirm Reset</button>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
};

export default UserManagement;
