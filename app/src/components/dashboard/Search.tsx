import React from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome'

const Search: React.FC = () => {
  return (
    <div className="container mt-5">
      <h1>Welcome to your Dashboard</h1>
      <div className="input-group">
                <input className="form-control border-end-0 border" type="search" value="" placeholder='Search here' id="example-search-input"/>
                <span className="input-group-append">
                    <button className="btn btn-outline-secondary bg-white border-start-0 border-bottom-0 border ms-n5" type="button">
                        {/* <i className="fa fa-search"></i> */}
                        Search
                    </button>
                </span>
            </div>
    </div>
  );
};

export default Search;
