import React, { useState, useEffect } from 'react';
import api from '../api';
import { FileText, Search, PlusCircle } from 'lucide-react';

const MemosPage = () => {
    const [memos, setMemos] = useState([]);
    const [searchTerm, setSearchTerm] = useState('');

    useEffect(() => {
        fetchMemos();
    }, []);

    const fetchMemos = async () => {
        try {
            const res = await api.get('/memos');
            setMemos(res.data);
        } catch (err) {
            console.error('Error fetching memos:', err);
        }
    };

    const filteredMemos = memos.filter(memo => 
        memo.memo_id.toLowerCase().includes(searchTerm.toLowerCase()) ||
        memo.customer_name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
        memo.department.toLowerCase().includes(searchTerm.toLowerCase())
    );

    return (
        <div style={{ padding: '10px' }}>
            <div className="glass-card">
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '25px', flexWrap: 'wrap', gap: '15px' }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                        <FileText className="text-primary" />
                        <h3 style={{ margin: 0 }}>System Memos</h3>
                    </div>
                    <div style={{ position: 'relative', width: '300px' }}>
                        <Search size={18} style={{ position: 'absolute', left: '12px', top: '50%', transform: 'translateY(-50%)', color: 'var(--text-muted)' }} />
                        <input 
                            type="text" 
                            placeholder="Search by Memo ID, Customer or Dept..." 
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                            style={{ 
                                width: '100%', 
                                padding: '10px 10px 10px 40px', 
                                background: 'white', 
                                border: '1px solid #e2e8f0', 
                                borderRadius: '8px',
                                outline: 'none'
                            }}
                        />
                    </div>
                </div>

                <div style={{ overflowX: 'auto' }}>
                    <table style={{ width: '100%', borderCollapse: 'collapse' }}>
                        <thead>
                            <tr style={{ borderBottom: '1px solid var(--glass-border)', textAlign: 'left' }}>
                                <th style={{ padding: '12px' }}>Memo ID</th>
                                <th style={{ padding: '12px' }}>Customer</th>
                                <th style={{ padding: '12px' }}>Department</th>
                                <th style={{ padding: '12px' }}>Category</th>
                                <th style={{ padding: '12px' }}>Created By</th>
                                <th style={{ padding: '12px' }}>File</th>
                                <th style={{ padding: '12px' }}>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            {filteredMemos.length > 0 ? filteredMemos.map(memo => (
                                <tr key={memo.id} style={{ borderBottom: '1px solid var(--glass-border)', transition: 'background 0.2s' }}>
                                    <td style={{ padding: '12px', fontWeight: '600', color: 'var(--primary)' }}>{memo.memo_id}</td>
                                    <td style={{ padding: '12px' }}>{memo.customer_name}</td>
                                    <td style={{ padding: '12px' }}>{memo.department}</td>
                                    <td style={{ padding: '12px' }}>{memo.category}</td>
                                    <td style={{ padding: '12px' }}>{memo.creator_name}</td>
                                    <td style={{ padding: '12px' }}>
                                        {memo.file_url ? (
                                            <a href={`http://localhost:5000${memo.file_url}`} target="_blank" rel="noopener noreferrer" style={{ color: 'var(--primary)', textDecoration: 'none', fontWeight: '500' }}>View File</a>
                                        ) : <span style={{ color: 'var(--text-muted)' }}>No File</span>}
                                    </td>
                                    <td style={{ padding: '12px', fontSize: '0.85rem', color: 'var(--text-muted)' }}>
                                        {new Date(memo.created_at).toLocaleDateString()}
                                    </td>
                                </tr>
                            )) : (
                                <tr>
                                    <td colSpan="7" style={{ textAlign: 'center', padding: '30px', color: 'var(--text-muted)' }}>No memos found.</td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    );
};

export default MemosPage;
