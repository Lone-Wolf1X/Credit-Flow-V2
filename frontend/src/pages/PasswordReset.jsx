import React, { useState } from 'react';
import api from '../api';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';

const PasswordReset = () => {
    const [newPassword, setNewPassword] = useState('');
    const [confirmPassword, setConfirmPassword] = useState('');
    const navigate = useNavigate();
    const { user, setUser } = useAuth();

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (newPassword !== confirmPassword) {
            alert("Passwords don't match");
            return;
        }
        try {
            await api.post('/auth/reset-password', { newPassword });
            setUser(prev => ({ ...prev, must_reset_password: false }));
            alert('Password updated successfully');
            navigate('/');
        } catch (err) {
            console.error('Password reset error:', err);
            alert(err.response?.data?.message || 'Failed to update password');
        }
    };

    return (
        <div style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', height: '100vh' }}>
            <div className="glass-card" style={{ width: '400px' }}>
                <h2 style={{ textAlign: 'center', marginBottom: '20px' }}>Reset Your Password</h2>
                <p style={{ color: 'var(--text-muted)', marginBottom: '20px', textAlign: 'center' }}>
                    This is your first login. Please set a new password.
                </p>
                <form onSubmit={handleSubmit}>
                    <div className="form-group" style={{ marginBottom: '15px' }}>
                        <label>New Password</label>
                        <input type="password" value={newPassword} onChange={(e) => setNewPassword(e.target.value)} required />
                    </div>
                    <div className="form-group" style={{ marginBottom: '20px' }}>
                        <label>Confirm Password</label>
                        <input type="password" value={confirmPassword} onChange={(e) => setConfirmPassword(e.target.value)} required />
                    </div>
                    <button type="submit" className="btn btn-primary" style={{ width: '100%' }}>Update Password</button>
                </form>
            </div>
        </div>
    );
};

export default PasswordReset;
