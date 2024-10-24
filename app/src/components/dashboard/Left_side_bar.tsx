import React from 'react';
import profileImage from '../../assets/images/profile.jpg';


const LeftBar: React.FC = () => {
    return (
        <>
            <div className="mt-5 friend_user_list">
                <div className="media-body">
                    <p className="">
                        <img className="profile_image" src={profileImage} alt="Generic placeholder image" />
                        &nbsp; <b>Ahsan Habib</b>
                    </p>
                </div>
                <hr />
                <ul className="list-unstyled">
                    <li className=" profile-list media">
                        <div className="media-body">
                            <p className="mt-0" >
                                <img className="profile_image" src={profileImage} alt="Generic placeholder image" />
                                &nbsp; Friend 1
                            </p>
                        </div>
                    </li>
                    <li className="profile-list media">
                        <div className="media-body">
                            <p className="mt-0">
                                <img className="profile_image mr-3" src={profileImage} alt="Generic placeholder image" />
                                &nbsp; Friend 2
                            </p>
                        </div>
                    </li>
                    <li className="profile-list media">
                        <div className="media-body">
                            <p className="mt-0">
                                <img className="profile_image mr-3" src={profileImage} alt="Generic placeholder image" />
                                &nbsp; Friend 3
                            </p>
                        </div>
                    </li>
                </ul>
            </div>
        </>
    );
};

export default LeftBar;
