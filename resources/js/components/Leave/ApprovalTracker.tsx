import React from 'react';
import { CheckCircle, Clock, XCircle } from 'lucide-react';

interface Approval {
    stage: number;
    status: string;
    comments?: string;
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
        <div className="flex items-center justify-between w-full max-w-xs py-2">
            {steps.map((step, index) => {
                const approval = approvals.find(a => a.stage === step.level);
                const isRejected = approval?.status === 'rejected' || (status === 'rejected' && currentStage === step.level);

                // Logic: 
                // Passed (step < current) -> Gray (History)
                // Current (step == current) -> Highlighted Blue (Active)
                // Future (step > current) -> Blue (Pending/Future) - "those left need to be blue"

                const isPassed = step.level < currentStage || (status === 'approved' && step.level === 4) || (approval?.status === 'approved');
                const isCurrent = step.level === currentStage && status !== 'approved' && status !== 'rejected';
                // If not passed and not rejected, it's future/left (unless it's current)

                // Refined Color Classes
                let circleClass = "";
                let textClass = "";

                if (isRejected) {
                    circleClass = "bg-red-100 text-red-600";
                    textClass = "text-red-600";
                } else if (isPassed) {
                    // Passed -> Gray
                    circleClass = "bg-gray-100 text-gray-400";
                    textClass = "text-gray-400";
                } else if (isCurrent) {
                    // Current -> Highlighted Blue
                    circleClass = "bg-blue-100 text-blue-600 ring-2 ring-blue-500 ring-offset-1";
                    textClass = "text-blue-700 font-bold";
                } else {
                    // Future/Left -> Blue
                    circleClass = "bg-blue-50 text-blue-400 border border-blue-100";
                    textClass = "text-blue-400";
                }

                return (
                    <React.Fragment key={step.level}>
                        <div className="flex flex-col items-center relative group">
                            <div className={`p-1.5 rounded-full transition-all duration-300 ${circleClass}`}>
                                {isRejected ? <XCircle size={16} /> :
                                    isPassed ? <CheckCircle size={16} /> :
                                        <Clock size={16} className={isCurrent ? "animate-pulse" : ""} />}
                            </div>

                            <span className={`text-[10px] mt-1 font-medium whitespace-nowrap ${textClass}`}>
                                {step.label}
                            </span>

                            {approval?.comments && (
                                <div className="absolute bottom-full mb-2 left-1/2 -translate-x-1/2 w-32 bg-gray-900 text-white text-[10px] p-1.5 rounded shadow-lg z-20 invisible group-hover:visible transition-opacity opacity-0 group-hover:opacity-100 pointer-events-none">
                                    {approval.comments}
                                    <div className="absolute top-100 left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900"></div>
                                </div>
                            )}
                        </div>
                        {index < steps.length - 1 && (
                            <div className={`h-px flex-1 mx-1 ${step.level < currentStage ? 'bg-gray-200' : 'bg-blue-100'
                                }`} />
                        )}
                    </React.Fragment>
                );
            })}
        </div>
    );
}
