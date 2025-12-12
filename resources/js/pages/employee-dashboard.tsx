import React, { useState, useEffect } from 'react';
import { PageTemplate } from '@/components/page-template';
import { RefreshCw, Bell, Users } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { useTranslation } from 'react-i18next';
import { usePage, router } from '@inertiajs/react';
import { toast } from '@/components/custom-toast';
import { format } from 'date-fns';

interface EmployeeDashboardData {
  stats: {
    totalAwards: number;
    totalWarnings: number;
    totalComplaints: number;
  };
  userType: string;
}

interface PageAction {
  label: string;
  icon: React.ReactNode;
  variant: 'default' | 'destructive' | 'outline' | 'secondary' | 'ghost' | 'link';
  onClick: () => void;
}

export default function EmployeeDashboard({ dashboardData }: { dashboardData: EmployeeDashboardData }) {
  const { t } = useTranslation();
  const { auth } = usePage().props as any;
  const [isClockedIn, setIsClockedIn] = useState(false);
  const [clockInTime, setClockInTime] = useState<string | null>(null);
  const [clockOutTime, setClockOutTime] = useState<string | null>(null);
  const [currentTime, setCurrentTime] = useState<string>('');

  useEffect(() => {
    const attendance = dashboardData?.todayAttendance;
    if (attendance) {
      if (attendance.clock_in) {
        setClockInTime(new Date(`1970-01-01T${attendance.clock_in}`).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: false }));
        setIsClockedIn(!attendance.clock_out);
      }
      if (attendance.clock_out) {
        setClockOutTime(new Date(`1970-01-01T${attendance.clock_out}`).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: false }));
      }
    }

    // Auto refresh to check for auto clock out
    const checkAutoClockOut = () => {
      const shift = dashboardData?.employeeShift;
      const attendance = dashboardData?.todayAttendance;

      if (shift && attendance?.clock_in && !attendance?.clock_out && isClockedIn) {
        const now = new Date();
        const shiftEnd = new Date(`1970-01-01T${shift.end_time}`);
        const currentTime = new Date(`1970-01-01T${now.getHours()}:${now.getMinutes()}:${now.getSeconds()}`);

        if (currentTime > shiftEnd) {
          window.location.reload();
        }
      }
    };

    const interval = setInterval(checkAutoClockOut, 60000); // Check every minute
    return () => clearInterval(interval);
  }, [dashboardData, isClockedIn]);

  const handleClockIn = () => {
    toast.loading(t('Clocking in...'));

    router.post(route('hr.attendance.clock-in'), {
      employee_id: auth.user.id
    }, {
      onSuccess: (page) => {
        toast.dismiss();
        if (page.props.flash?.success) {
          setIsClockedIn(true);
          setClockInTime(new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: false }));
          toast.success(t(page.props.flash.success));
        } else {
          toast.error(t(page.props.flash.error || 'Failed to clock in'));
        }
      },
      onError: (errors, page) => {
        toast.dismiss();
        if (page?.props?.flash?.error) {
          toast.error(t(page.props.flash.error));
        } else if (typeof errors === 'string') {
          toast.error(errors);
        } else if (errors && Object.keys(errors).length > 0) {
          const firstError = Object.values(errors)[0];
          toast.error(Array.isArray(firstError) ? firstError[0] : firstError);
        } else {
          toast.error(t('Failed to clock in. Please try again.'));
        }
      }
    });
  };

  const handleClockOut = () => {
    toast.loading(t('Clocking out...'));

    router.post(route('hr.attendance.clock-out'), {
      employee_id: auth.user.id
    }, {
      onSuccess: (page) => {
        toast.dismiss();
        if (page.props.flash?.success) {
          setIsClockedIn(false);
          setClockOutTime(new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: false }));
          toast.success(t(page.props.flash.success));
        } else {
          toast.error(t(page.props.flash.error || 'Failed to clock out'));
        }
      },
      onError: (errors, page) => {
        toast.dismiss();
        if (page?.props?.flash?.error) {
          toast.error(t(page.props.flash.error));
        } else if (typeof errors === 'string') {
          toast.error(errors);
        } else if (errors && Object.keys(errors).length > 0) {
          const firstError = Object.values(errors)[0];
          toast.error(Array.isArray(firstError) ? firstError[0] : firstError);
        } else {
          toast.error(t('Failed to clock out. Please try again.'));
        }
      }
    });
  };

  const pageActions: PageAction[] = [
    {
      label: t('Refresh'),
      icon: <RefreshCw className="h-4 w-4" />,
      variant: 'outline',
      onClick: () => window.location.reload()
    }
  ];

  const stats = dashboardData?.stats || {
    totalAwards: 0,
    totalWarnings: 0,
    totalComplaints: 0
  };


  const getStatusColor = (status: string) => {
    const colors = {
      'approved': 'bg-green-50 text-green-700 ring-green-600/20',
      'pending': 'bg-yellow-50 text-yellow-700 ring-yellow-600/20',
      'rejected': 'bg-red-50 text-red-700 ring-red-600/20'
    };
    return colors[status] || 'bg-gray-50 text-gray-700 ring-gray-600/20';
  };

  return (
    <PageTemplate
      title={t('Dashboard')}
      url="/dashboard"
      actions={pageActions}
    >
      <div className="space-y-6">
        {/* Welcome Message */}
        <Card>
          <CardContent className="p-6">
            <div className="text-center">
              <h1 className="text-2xl font-bold mb-2">Welcome, {auth.user?.name}!</h1>
              <p className="text-muted-foreground">Stay updated with company announcements and meetings</p>
            </div>
          </CardContent>
        </Card>

        {/* Employee Stats */}
        <div className="grid gap-6 grid-cols-1 md:grid-cols-3">
          <Card>
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-muted-foreground">{t('Total Awards')}</p>
                  <p className="mt-2 text-2xl font-bold">{stats.totalAwards}</p>
                </div>
                <div className="rounded-full bg-green-100 p-3 dark:bg-green-900">
                  <svg className="h-5 w-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                  </svg>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-muted-foreground">{t('Total Warnings')}</p>
                  <p className="mt-2 text-2xl font-bold">{stats.totalWarnings}</p>
                </div>
                <div className="rounded-full bg-yellow-100 p-3 dark:bg-yellow-900">
                  <svg className="h-5 w-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                  </svg>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-muted-foreground">{t('Total Complaints')}</p>
                  <p className="mt-2 text-2xl font-bold">{stats.totalComplaints}</p>
                </div>
                <div className="rounded-full bg-red-100 p-3 dark:bg-red-900">
                  <svg className="h-5 w-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Clock In/Out Card */}
        <Card>
          <CardContent className="p-6">
            <div className="text-center">
              <h2 className="text-lg font-semibold mb-4">{t('Attendance')}</h2>

              {/* Shift Information */}
              {dashboardData?.employeeShift && (
                <div className="mb-4 p-3 bg-blue-50 rounded-lg border border-blue-200">
                  <p className="text-sm text-blue-600 font-medium">
                    {dashboardData.employeeShift.name} {dashboardData.employeeShift.start_time} to {dashboardData.employeeShift.end_time}
                  </p>
                </div>
              )}

              {/* Clock In/Out Buttons */}
              <div className="flex justify-center gap-4 mb-6">
                <button
                  className={`flex items-center justify-center px-8 py-4 rounded-lg font-semibold transition-colors shadow-md ${isClockedIn
                    ? 'bg-gray-400 cursor-not-allowed text-white'
                    : 'bg-green-500 hover:bg-green-600 text-white'
                    }`}
                  onClick={handleClockIn}
                  disabled={isClockedIn}
                >
                  <svg className="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6v6l4 2" />
                    <circle cx="12" cy="12" r="10" />
                  </svg>
                  {t('Clock In')}
                </button>

                <button
                  className={`flex items-center justify-center px-8 py-4 rounded-lg font-semibold transition-colors shadow-md ${!isClockedIn
                    ? 'bg-gray-400 cursor-not-allowed text-white'
                    : 'bg-red-500 hover:bg-red-600 text-white'
                    }`}
                  onClick={handleClockOut}
                  disabled={!isClockedIn}
                >
                  <svg className="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                  </svg>
                  {t('Clock Out')}
                </button>
              </div>

              {/* Clock Times Display */}
              <div className="grid grid-cols-2 gap-4">
                <div className="bg-green-50 p-4 rounded-lg border border-green-200">
                  <p className="text-sm text-green-600 font-medium mb-1">{t('Clock In Time')}</p>
                  <p className="text-lg font-bold text-green-800">{clockInTime || '--:-- --'}</p>
                  <p className="text-xs text-green-600">{clockInTime ? 'Today' : 'Not clocked in'}</p>
                </div>

                <div className="bg-red-50 p-4 rounded-lg border border-red-200">
                  <p className="text-sm text-red-600 font-medium mb-1">{t('Clock Out Time')}</p>
                  <p className="text-lg font-bold text-red-800">{clockOutTime || '--:-- --'}</p>
                  <p className="text-xs text-red-600">{clockOutTime ? 'Today' : 'Not clocked out'}</p>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

      </div>
    </PageTemplate>
  );
}