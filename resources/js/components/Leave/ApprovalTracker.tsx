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
        <div className="flex items-center space-x-8 py-4">
            {steps.map((step, index) => {
                const approval = approvals.find(a => a.stage === step.level);
                const isRejected = approval?.status === 'rejected' || (status === 'rejected' && currentStage === step.level);
                const isCompleted = step.level < currentStage || (status === 'approved' && step.level === 4) || (approval?.status === 'approved');
                const isCurrent = step.level === currentStage && status === 'pending';

                return (
                    <React.Fragment key={step.level}>
                        <div className="flex flex-col items-center relative">
                            <div className={`p-3 rounded-full transition-all duration-300 ${isRejected ? 'bg-red-100 text-red-600' :
                                isCompleted ? 'bg-green-100 text-green-600' :
                                    isCurrent ? 'bg-blue-100 text-blue-600 ring-2 ring-blue-500 ring-offset-2' :
                                        'bg-gray-100 text-gray-400'
                                }`}>
                                {isRejected ? <XCircle size={24} /> :
                                    isCompleted ? <CheckCircle size={24} /> :
                                        <Clock size={24} className={isCurrent ? "animate-pulse" : ""} />}
                            </div>
                            <span className={`text-xs mt-2 font-medium ${isCurrent ? 'text-blue-700' : 'text-gray-600'
                                }`}>{step.label}</span>

                            {approval?.comments && (
                                <div className="absolute top-14 left-1/2 -translate-x-1/2 w-48 bg-white p-2 rounded shadow-lg border text-[10px] z-10 invisible group-hover:visible">
                                    <strong>Note:</strong> {approval.comments}
                                </div>
                            )}
                        </div>
                        {index < steps.length - 1 && (
                            <div className={`h-px w-12 ${step.level < currentStage ? 'bg-green-500' : 'bg-gray-300'
                                }`} />
                        )}
                    </React.Fragment>
                );
            })}
        </div>
    );
}
