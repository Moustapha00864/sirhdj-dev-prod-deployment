import { useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { Save, Calendar, ShieldCheck, Clock } from 'lucide-react';
import { toast } from '@/components/custom-toast';
import { useTranslation } from 'react-i18next';
import { Card, CardHeader, CardTitle, CardContent, CardFooter } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';

export default function LeaveSettings() {
    const { t } = useTranslation();
    const { settings, auth } = usePage().props as any;
    const [formData, setFormData] = useState({
        holiday_exclusion: settings.holiday_exclusion || 'none',
        carry_over_limit: settings.carry_over_limit || 0,
        default_initial_balance: settings.default_initial_balance || 21,
        weekend_exclusion: settings.weekend_exclusion || 'both',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        toast.loading(t('Updating leave settings...'));

        router.post(route('hr.leave-settings.store'), formData, {
            onSuccess: (page: any) => {
                toast.dismiss();
                if (page.props.flash.success) {
                    toast.success(t(page.props.flash.success));
                }
            },
            onError: (errors) => {
                toast.dismiss();
                toast.error(t('Failed to update settings'));
            }
        });
    };

    const breadcrumbs = [
        { title: t('Dashboard'), href: route('dashboard') },
        { title: t('Leave Management'), href: route('hr.leave-balances.index') },
        { title: t('Global Settings') }
    ];

    return (
        <PageTemplate
            title={t("Global Leave Settings")}
            description={t("Configure global hospital leave policies")}
            url="/hr/leave-settings"
            breadcrumbs={breadcrumbs}
        >
            <div className="max-w-4xl mx-auto py-6">
                <form onSubmit={handleSubmit}>
                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-primary">
                                    <Calendar className="h-5 w-5" />
                                    {t('Holiday & Weekend Rules')}
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="holiday_exclusion">{t('Holiday Exclusion Policy')}</Label>
                                        <Select
                                            value={formData.holiday_exclusion}
                                            onValueChange={(value) => setFormData({ ...formData, holiday_exclusion: value })}
                                        >
                                            <SelectTrigger id="holiday_exclusion">
                                                <SelectValue placeholder={t('Select policy')} />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="none">{t('Include all dates (No exclusion)')}</SelectItem>
                                                <SelectItem value="national">{t('Exclude National Holidays')}</SelectItem>
                                                <SelectItem value="branch">{t('Exclude Branch-specific Holidays')}</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p className="text-xs text-muted-foreground">
                                            {t('Determines which holidays are subtracted from the total leave duration.')}
                                        </p>
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="weekend_exclusion">{t('Weekend Exclusion')}</Label>
                                        <Select
                                            value={formData.weekend_exclusion}
                                            onValueChange={(value) => setFormData({ ...formData, weekend_exclusion: value })}
                                        >
                                            <SelectTrigger id="weekend_exclusion">
                                                <SelectValue placeholder={t('Select weekend days')} />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="none">{t('Include Weekends')}</SelectItem>
                                                <SelectItem value="saturday">{t('Exclude Saturdays')}</SelectItem>
                                                <SelectItem value="sunday">{t('Exclude Sundays')}</SelectItem>
                                                <SelectItem value="both">{t('Exclude Saturday & Sunday')}</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-primary">
                                    <Clock className="h-5 w-5" />
                                    {t('Balance & Carry-over')}
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="default_initial_balance">{t('Default Annual Allocation')}</Label>
                                        <Input
                                            type="number"
                                            id="default_initial_balance"
                                            value={formData.default_initial_balance}
                                            onChange={(e) => setFormData({ ...formData, default_initial_balance: parseFloat(e.target.value) })}
                                            min="0"
                                            step="0.5"
                                        />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="carry_over_limit">{t('Carry-over Limit (Days)')}</Label>
                                        <Input
                                            type="number"
                                            id="carry_over_limit"
                                            value={formData.carry_over_limit}
                                            onChange={(e) => setFormData({ ...formData, carry_over_limit: parseInt(e.target.value) })}
                                            min="0"
                                        />
                                        <p className="text-xs text-muted-foreground">
                                            {t('Maximum remaining days that can be carried to the next year.')}
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <div className="flex justify-end">
                            <Button type="submit" className="gap-2">
                                <Save className="h-4 w-4" />
                                {t('Save Changes')}
                            </Button>
                        </div>
                    </div>
                </form>
            </div>
        </PageTemplate>
    );
}
