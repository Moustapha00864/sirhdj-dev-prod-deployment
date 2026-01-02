import React from 'react';
import { router } from '@inertiajs/react';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Textarea } from "@/components/ui/textarea";
import { toast } from '@/components/custom-toast';
import { useTranslation } from 'react-i18next';
import axios from 'axios';

interface BulkApprovalModalProps {
    selectedIds: number[];
    isOpen: boolean;
    onClose: () => void;
    onSuccess: () => void;
}

export default function BulkApprovalModal({ selectedIds, isOpen, onClose, onSuccess }: BulkApprovalModalProps) {
    const { t } = useTranslation();
    const [comments, setComments] = React.useState('');
    const [processing, setProcessing] = React.useState(false);

    const handleBulkAction = async (action: 'approve' | 'reject') => {
        setProcessing(true);

        const routeName = action === 'approve'
            ? 'hr.leave-applications.bulk-approve'
            : 'hr.leave-applications.bulk-reject';

        try {
            const response = await axios.post(route(routeName), {
                leave_application_ids: selectedIds,
                manager_comments: comments || null
            });

            const data = response.data;

            // Show success message
            if (data.successful > 0) {
                toast.success(data.message);
            }

            // Show errors if any
            if (data.errors && data.errors.length > 0) {
                data.errors.forEach((error: string) => {
                    toast.error(error);
                });
            }

            // Close modal and refresh
            onClose();
            setComments('');
            onSuccess();

            // Refresh the page to show updated data
            router.reload();
        } catch (error: any) {
            console.error('Bulk action error:', error);
            if (error.response?.data?.message) {
                toast.error(error.response.data.message);
            } else {
                toast.error(t('An error occurred while processing the request'));
            }
        } finally {
            setProcessing(false);
        }
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-[500px]">
                <DialogHeader>
                    <DialogTitle>
                        {t('Bulk Action on :count Leave Application(s)', { count: selectedIds.length })}
                    </DialogTitle>
                </DialogHeader>
                <div className="grid gap-4 py-4">
                    <div className="flex flex-col gap-2">
                        <p className="text-sm text-gray-600 dark:text-gray-400">
                            {t('You have selected :count leave application(s). Add optional comments below:', { count: selectedIds.length })}
                        </p>
                        <label className="text-sm font-medium">{t('Comments (Optional)')}</label>
                        <Textarea
                            value={comments}
                            onChange={e => setComments(e.target.value)}
                            placeholder={t('Add comments or reason...')}
                            className="min-h-[100px]"
                            disabled={processing}
                        />
                    </div>
                </div>
                <DialogFooter className="flex gap-2 sm:justify-end">
                    <Button
                        variant="outline"
                        onClick={onClose}
                        disabled={processing}
                    >
                        {t('Cancel')}
                    </Button>
                    <Button
                        variant="destructive"
                        onClick={() => handleBulkAction('reject')}
                        disabled={processing}
                    >
                        {processing ? t('Processing...') : t('Reject Selected')}
                    </Button>
                    <Button
                        variant="default"
                        className="bg-green-600 hover:bg-green-700"
                        onClick={() => handleBulkAction('approve')}
                        disabled={processing}
                    >
                        {processing ? t('Processing...') : t('Approve Selected')}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
