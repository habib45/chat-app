import React from 'react';
import LeftBar from './Left_side_bar';
import Search from './Search';
import '../../assets/css/dashboard.css'

const Dashboard: React.FC = () => {
  return (
    <>
      <div className="container-fluid main-div">
        <div className='row'>
          <div className='col-3 sid_bar'>
            <LeftBar />
          </div>
          <div className='col-9 chat_bar'>
            <Search />
          </div>
        </div>
      </div>
    </>
  );
};

export default Dashboard;
