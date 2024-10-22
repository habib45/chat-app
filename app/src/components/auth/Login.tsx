import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { useNavigate } from 'react-router-dom';

const Login: React.FC = () => {
  const [email, setEmail] = useState<string>('');
  const [password, setPassword] = useState<string>('');
  const [error, setError] = useState<string>('');
  
  const navigate = useNavigate();
// Check if the user is already logged in (authToken exists)
 // Run this effect only once when the component loads

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!email || !password) {
      setError('Please fill in all fields.');
      return;
    }
    try {
        // Replace with your API endpoint
        const response = await axios.post('http://localhost/real_chat/backend/public/index.php/api/v1/login', { email, password });
        
        if(response.data.status_code==200){
            const obj = response.data.data;
          console.log(response.data);
          console.log(response.data.status_code);
          const { token } = response.data.data.token;
           // Store token in localStorage
          localStorage.setItem('authToken', obj.token);
          localStorage.setItem('user', obj.user);
          // Navigate to dashboard on successful login
         
          navigate('/dashboard');
          window.location.reload();
        }else{
          console.log(response.data);
          setError(response.data.message);
          // Navigate to login page
        }
        
      } catch (err) {
        console.log(err);
        setError('Invalid credentials. Please try again.');
      }
 
  };

  return (
    <>
    <div className="d-flex md-12">
       <h2>Real Chat Application </h2>
    </div>
    <div className="d-flex justify-content-center align-items-center vh-90">
     
      <div className="card p-4" style={{ maxWidth: '400px', width: '100%' }}>
        <h3 className="text-center mb-4">Login</h3>
        {error && <div className="alert alert-danger">{error}</div>}
        <form onSubmit={handleSubmit}>
          <div className="mb-3">
            <label htmlFor="email" className="form-label">Email</label>
            <input
              type="email"
              className="form-control"
              id="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
            />
          </div>
          <div className="mb-3">
            <label htmlFor="password" className="form-label">Password</label>
            <input
              type="password"
              className="form-control"
              id="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
            />
          </div>
          <button type="submit" className="btn btn-primary w-100">Login</button>
        </form>
      </div>
    </div>
   </>
  );
};

export default Login;
