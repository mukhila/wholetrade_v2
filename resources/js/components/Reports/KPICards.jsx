import React from 'react';

const KPICards = ({ data }) => {
    return (
        <div className="row mb-3">
            {data.map((kpi, index) => (
                <div key={index} className="col-md-3 col-lg-2-4">
                    <div className={`card border-${kpi.color} mb-3`}>
                        <div className="card-body">
                            <div className="d-flex align-items-center">
                                <div className={`flex-shrink-0 avatar-sm rounded-circle bg-${kpi.color} bg-opacity-10`}>
                                    <span className={`avatar-title text-${kpi.color} fs-3`}>
                                        <i className={`ti ${kpi.icon}`}></i>
                                    </span>
                                </div>
                                <div className="flex-grow-1 ms-3">
                                    <p className="text-muted mb-1 fs-sm">{kpi.title}</p>
                                    <h4 className="mb-0">{kpi.value}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            ))}
        </div>
    );
};

export default KPICards;
