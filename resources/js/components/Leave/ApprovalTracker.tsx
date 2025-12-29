import React from 'react';
import { CheckCircle, Clock, XCircle, Calendar } from 'lucide-react';

interface Approval {
    stage: number;
    status: string;
    comments?: string;
    updated_at?: string;
}

interface ApprovalTrackerProps {
    currentStage: number;
    status: string;
    approvals: Approval[];
}

export default function ApprovalTracker({ currentStage, status, approvals }: ApprovalTrackerProps) {
    const steps = [
        { level: 1, label: 'Manager L1' },
        { level: 2, label: 'Manager L2' },
        { level: 3, label: 'RH Review' },
        { level: 4, label: 'Direction' }
    ];

    return (
        <div className="flex flex-col w-full max-w-md py-4">
            {/* Progress Bar Container */}
            <div className="flex items-center justify-between w-full relative">
                {/* Line Background */}
                <div className="absolute top-1/2 left-0 w-full h-1 bg-gray-200 -z-10 rounded"></div>
                {/* This static line is too simple, we need dynamic segments between steps */}

                {steps.map((step, index) => {
                    const approval = approvals.find(a => a.stage === step.level);
                    const isRejected = approval?.status === 'rejected' || (status === 'rejected' && currentStage === step.level);
                    const isPassed = step.level < currentStage || (status === 'approved' && step.level === 4) || (approval?.status === 'approved');
                    const isCurrent = step.level === currentStage && status !== 'approved' && status !== 'rejected';

                    // Logic: 
                    // Passed -> Blue (Validated)
                    // Current -> Blue (Active/Pulse)
                    // Future -> Gray (Pending)

                    let circleClass = "";
                    let textClass = "";
                    let icon = null;

                    if (isRejected) {
                        circleClass = "bg-red-100 text-red-600 ring-2 ring-red-500 bg-white";
                        textClass = "text-red-600 font-medium";
                        icon = <XCircle size={20} />;
                    } else if (isPassed) {
                        // Passed -> Blue
                        circleClass = "bg-blue-100 text-blue-600 ring-2 ring-blue-500 bg-white";
                        textClass = "text-blue-700 font-medium";
                        icon = <CheckCircle size={20} />;
                    } else if (isCurrent) {
                        // Current -> Highlighted Blue Pulse
                        circleClass = "bg-blue-50 text-blue-500 ring-2 ring-blue-400 ring-offset-2 animate-pulse bg-white";
                        textClass = "text-blue-600 font-bold";
                        icon = <Clock size={20} />;
                    } else {
                        // Future -> Gray
                        circleClass = "bg-gray-100 text-gray-400 ring-2 ring-gray-200 bg-white";
                        textClass = "text-gray-400";
                        icon = <Clock size={20} />;
                    }

                    // Connecting Line Logic (Visual fix needed)
                    // We render lines manually to handle colors correctly

                    return (
                        <React.Fragment key={step.level}>
                            {/* Connector Line (Preceding) */}
                            {index > 0 && (
                                <div
                                    className={`absolute h-1 top-1/2 -z-10 transition-colors duration-300`}
                                    style={{
                                        left: `${(100 / (steps.length - 1)) * (index - 1)}%`,
                                        width: `${100 / (steps.length - 1)}%`,
                                        backgroundColor: (isPassed || isCurrent || (isRejected && currentStage >= step.level)) ? '#3b82f6' : '#e5e7eb' // blue-500 or gray-200
                                    }}
                                />
                            )}
                            {/* But using absolute positioning for lines is tricky with flex space-between. 
                                 Better to stick to the flex layout but make lines distinct elements if possible?
                                 Actually the previous implementation used flex items. Let's revert to a robust flex layout.
                             */}
                        </React.Fragment>
                    );
                })}

                {/* Re-render using a cleaner layout approach */}
            </div>

            <div className="flex items-start justify-between w-full relative">
                {steps.map((step, index) => {
                    const approval = approvals.find(a => a.stage === step.level);
                    // Determine status logic again
                    const isRejected = approval?.status === 'rejected' || (status === 'rejected' && currentStage === step.level);
                    const isPassed = step.level < currentStage || (status === 'approved' && step.level === 4) || (approval?.status === 'approved');
                    const isCurrent = step.level === currentStage && status !== 'approved' && status !== 'rejected';

                    let circleClass = "";
                    let textClass = "";

                    if (isRejected) {
                        circleClass = "bg-white text-red-600 ring-2 ring-red-500";
                        textClass = "text-red-700 font-bold";
                    } else if (isPassed) {
                        circleClass = "bg-white text-blue-600 ring-2 ring-blue-500";
                        textClass = "text-blue-700 font-bold";
                    } else if (isCurrent) {
                        circleClass = "bg-white text-blue-500 ring-2 ring-blue-400 ring-offset-2";
                        textClass = "text-blue-600 font-bold";
                    } else {
                        circleClass = "bg-white text-gray-300 ring-2 ring-gray-200";
                        textClass = "text-gray-400 font-medium";
                    }

                    return (
                        <div key={step.level} className="flex-1 flex flex-col items-center relative first:items-start last:items-end w-full">

                            {/* Connector Line */}
                            {index < steps.length - 1 && (
                                <div className={`absolute top-3.5 left-[50%] w-full h-0.5 -z-10 ${isPassed ? 'bg-blue-500' : 'bg-gray-200'
                                    }`}></div>
                            )}

                            {/* Circle Icon */}
                            <div className={`w-7 h-7 rounded-full flex items-center justify-center z-10 transition-all duration-300 ${circleClass} ${isCurrent ? 'scale-110' : ''}`}>
                                {isRejected ? <XCircle size={16} /> :
                                    isPassed ? <CheckCircle size={16} /> :
                                        <Clock size={16} className={isCurrent ? "animate-spin-slow" : ""} />}
                            </div>

                            {/* Label */}
                            <div className={`mt-2 flex flex-col items-center ${index === 0 ? 'items-start' : index === steps.length - 1 ? 'items-end' : 'items-center'}`}>
                                <span className={`text-[10px] whitespace-nowrap ${textClass}`}>
                                    {step.label}
                                </span>
                                {/* Date */}
                                {approval?.updated_at && isPassed && (
                                    <span className="text-[9px] text-gray-400 flex items-center gap-1 mt-0.5">
                                        {new Date(approval.updated_at).toLocaleDateString()}
                                    </span>
                                )}
                            </div>

                            {/* Hover Comments */}
                            {approval?.comments && (
                                <div className="absolute top-8 w-max max-w-[150px] bg-gray-800 text-white text-[10px] p-2 rounded shadow-lg opacity-0 hover:opacity-100 transition-opacity z-20 pointer-events-none">
                                    {approval.comments}
                                </div>
                            )}
                        </div>
                    );
                })}
            </div>
        </div>
    );
}

