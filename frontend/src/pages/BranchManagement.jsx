import React, { useState, useEffect } from 'react';
import api from '../api';

const BranchManagement = () => {
    const [branches, setBranches] = useState([]);
    const [formData, setFormData] = useState({ name: '', short_name: '', sol_id: '', location: '', province: '' });
    const [isEditing, setIsEditing] = useState(false);
    const [editId, setEditId] = useState(null);

    useEffect(() => {
        fetchBranches();
    }, []);

    const fetchBranches = async () => {
        const res = await api.get('/branches');
        setBranches(res.data);
    };

    const handleChange = (e) => setFormData({ ...formData, [e.target.name]: e.target.value });

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            if (isEditing) {
                await api.put(`/branches/${editId}`, formData);
            } else {
                await api.post('/branches', formData);
            }
            setFormData({ name: '', short_name: '', sol_id: '', location: '', province: '' });
            setIsEditing(false);
            fetchBranches();
            alert('Success!');
        } catch (err) {
            alert('Operation failed');
        }
    };

    const handleEdit = (branch) => {
        setFormData(branch);
        setIsEditing(true);
        setEditId(branch.id);
    };

    const handleDelete = async (id) => {
        if (window.confirm('Are you sure?')) {
            await api.delete(`/branches/${id}`);
            fetchBranches();
        }
    };

    return (
        <div className="glass-card">
            <h3>Branch Management</h3>
            <form onSubmit={handleSubmit} style={{ marginTop: '20px' }}>
                <div className="form-row">
                    <div className="form-group">
                        <label>Branch Name</label>
                        <input name="name" value={formData.name} onChange={handleChange} required />
                    </div>
                    <div className="form-group">
                        <label>Short Name</label>
                        <input name="short_name" value={formData.short_name} onChange={handleChange} required />
                    </div>
                    <div className="form-group">
                        <label>SOL ID</label>
                        <input name="sol_id" value={formData.sol_id} onChange={handleChange} required />
                    </div>
                </div>
                <div className="form-row">
                    <div className="form-group">
                        <label>Location</label>
                        <input name="location" value={formData.location} onChange={handleChange} required />
                    </div>
                    <div className="form-group">
                        <label>Province</label>
                        <select name="province" value={formData.province} onChange={handleChange} required>
                            <option value="">Select Province</option>
                            <option value="Koshi">Koshi</option>
                            <option value="Madhesh">Madhesh</option>
                            <option value="Bagmati">Bagmati</option>
                            <option value="Gandaki">Gandaki</option>
                            <option value="Lumbini">Lumbini</option>
                            <option value="Karnali">Karnali</option>
                            <option value="Sudurpashchim">Sudurpashchim</option>
                        </select>
                    </div>
                    <div className="form-group" style={{ display: 'flex', alignItems: 'flex-end' }}>
                        <button type="submit" className="btn btn-primary" style={{ width: '100%' }}>
                            {isEditing ? 'Update Branch' : 'Add Branch'}
                        </button>
                    </div>
                </div>
            </form>

            <table style={{ width: '100%', marginTop: '30px', borderCollapse: 'collapse' }}>
                <thead>
                    <tr style={{ borderBottom: '1px solid var(--glass-border)', textAlign: 'left' }}>
                        <th style={{ padding: '10px' }}>Branch Name</th>
                        <th style={{ padding: '10px' }}>Short Name</th>
                        <th style={{ padding: '10px' }}>SOL ID</th>
                        <th style={{ padding: '10px' }}>Location</th>
                        <th style={{ padding: '10px' }}>Province</th>
                        <th style={{ padding: '10px' }}>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {branches.map(b => (
                        <tr key={b.id} style={{ borderBottom: '1px solid var(--glass-border)' }}>
                            <td style={{ padding: '10px' }}>{b.name}</td>
                            <td style={{ padding: '10px' }}>{b.short_name}</td>
                            <td style={{ padding: '10px' }}>{b.sol_id}</td>
                            <td style={{ padding: '10px' }}>{b.location}</td>
                            <td style={{ padding: '10px' }}>{b.province}</td>
                            <td style={{ padding: '10px' }}>
                                <button className="btn" onClick={() => handleEdit(b)} style={{ marginRight: '5px' }}>Edit</button>
                                <button className="btn" onClick={() => handleDelete(b.id)} style={{ background: 'var(--danger)' }}>Delete</button>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
};

export default BranchManagement;
