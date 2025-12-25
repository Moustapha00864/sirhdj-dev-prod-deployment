import React from 'react';
import { useForm, router } from '@inertiajs/react';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Textarea } from "@/components/ui/textarea";

interface ApprovalModalProps {
    leaveId: number;
    isOpen: boolean;
    onClose: () => void;
}

export default function ApprovalModal({ leaveId, isOpen, onClose }: ApprovalModalProps) {
    const { data, setData, processing, reset } = useForm({
        status: 'approved',
        manager_comments: ''
    });

    const handleSubmit = (status: 'approved' | 'rejected') => {
        router.put(route('hr.leave-applications.update-status', leaveId), {
            status,
            manager_comments: data.manager_comments
        }, {
            onSuccess: () => {
                onClose();
                reset();
            }
        });
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>Décision sur la demande de congé</DialogTitle>
                </DialogHeader>
                <div className="grid gap-4 py-4">
                    <div className="flex flex-col gap-2">
                        <label className="text-sm font-medium">Commentaires (Optionnel)</label>
                        <Textarea
                            value={data.manager_comments}
                            onChange={e => setData('manager_comments', e.target.value)}
                            placeholder="Ajouter des remarques ou la raison du rejet..."
                            className="min-h-[100px]"
                        />
                    </div>
                </div>
                <DialogFooter className="flex gap-2 sm:justify-end">
                    <Button
                        variant="outline"
                        onClick={onClose}
                        disabled={processing}
                    >
                        Annuler
                    </Button>
                    <Button
                        variant="destructive"
                        onClick={() => handleSubmit('rejected')}
                        disabled={processing}
                    >
                        Rejeter
                    </Button>
                    <Button
                        variant="default"
                        className="bg-green-600 hover:bg-green-700"
                        onClick={() => handleSubmit('approved')}
                        disabled={processing}
                    >
                        Approuver
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
