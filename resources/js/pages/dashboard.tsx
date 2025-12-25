import React from 'react';
import { PageTemplate } from '@/components/page-template';
import { RefreshCw, Users, Building2, Briefcase, UserPlus, Calendar, Clock, TrendingUp, BarChart3, Bell, PersonStanding } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useTranslation } from 'react-i18next';
import { usePage } from '@inertiajs/react';
import { PieChart, Pie, Cell, ResponsiveContainer, Tooltip, BarChart, Bar, XAxis, YAxis, CartesianGrid, Legend, LineChart, Line, AreaChart, Area, PieChart as PieChartIcon } from 'recharts';

const COLORS = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'];

interface CompanyDashboardData {
  stats: {
    totalEmployees: number;
    totalBranches: number;
    totalDepartments: number;
    newEmployeesThisMonth: number;
    jobPostsThisMonth: number;
    candidatesThisMonth: number;
    attendanceRate: number;
    presentToday: number;
    pendingLeaves: number;
    pendingLeavesToValidate: number;
    leavesUsedThisYear: number;
    onLeaveToday: number;
    activeJobPostings: number;
    totalCandidates: number;
  };
  charts: {
    departmentStats: Array<{ name: string; value: number; color: string }>;
    hiringTrend: Array<{ month: string; hires: number; departures: number }>;
    candidateStatusStats: Array<{ name: string; value: number; color: string }>;
    leaveTypesStats: Array<{ name: string; value: number; color: string }>;
    employeeGrowthChart: Array<{ month: string; employees: number }>;
    leavePerDepartment: Array<{ name: string; value: number }>;
    ageDistribution: Array<{ age_group: string; total: number }>;
    genderByPersonnelType: Array<{ type: string; male: number; female: number }>;
    maritalStatusStats: Array<{ name: string; value: number }>;
  };
  tables: {
    staffByYearAndContract: Array<any>;
    medicalStaffStats: Array<any>;
    techniqueStaffStats: Array<any>;
  };
  userType: string;
}

interface PageAction {
  label: string;
  icon: React.ReactNode;
  variant: 'default' | 'destructive' | 'outline' | 'secondary' | 'ghost' | 'link';
  onClick: () => void;
}

export default function Dashboard({ dashboardData }: { dashboardData: CompanyDashboardData }) {
  const { t } = useTranslation();
  const { auth } = usePage().props as any;

  const pageActions: PageAction[] = [
    {
      label: t('Refresh'),
      icon: <RefreshCw className="h-4 w-4" />,
      variant: 'outline',
      onClick: () => window.location.reload()
    }
  ];

  const stats = dashboardData?.stats || {
    totalEmployees: 0,
    totalBranches: 0,
    totalDepartments: 0,
    newEmployeesThisMonth: 0,
    jobPostsThisMonth: 0,
    candidatesThisMonth: 0,
    attendanceRate: 0,
    presentToday: 0,
    pendingLeaves: 0,
    pendingLeavesToValidate: 0,
    leavesUsedThisYear: 0,
    onLeaveToday: 0,
    activeJobPostings: 0,
    totalCandidates: 0
  };

  const charts = dashboardData?.charts || {
    departmentStats: [],
    hiringTrend: [],
    candidateStatusStats: [],
    leaveTypesStats: [],
    employeeGrowthChart: [],
    leavePerDepartment: []
  };

  const userType = dashboardData?.userType || 'employee';
  const isCompanyUser = userType === 'company';

  const [searchStaff, setSearchStaff] = React.useState('');
  const [searchMedical, setSearchMedical] = React.useState('');
  const [searchTechnique, setSearchTechnique] = React.useState('');

  const tables = dashboardData?.tables || {
    staffByYearAndContract: [],
    medicalStaffStats: [],
    techniqueStaffStats: []
  };

  const filteredStaff = tables.staffByYearAndContract.filter(item =>
    item.year.toString().includes(searchStaff)
  );

  const filteredMedical = tables.medicalStaffStats.filter(item =>
    item.specialty.toLowerCase().includes(searchMedical.toLowerCase())
  );

  const filteredTechnique = tables.techniqueStaffStats.filter(item =>
    item.specialty.toLowerCase().includes(searchTechnique.toLowerCase())
  );

  return (
    <PageTemplate
      title={t('Dashboard')}
      url="/dashboard"
      actions={pageActions}
      description={t('Resumé de l\'activité RH')}
    >
      <div className="space-y-6">
        {/* Key Metrics */}
        <div className="grid gap-6 grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
          <Card>
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-muted-foreground">{t('Total Employees')}</p>
                  <p className="mt-2 text-2xl font-bold">{stats.totalEmployees}</p>
                  {isCompanyUser && (
                    <p className="text-xs text-green-600 mt-1">+{stats.newEmployeesThisMonth} {t('this month')}</p>
                  )}
                </div>
                <div className="rounded-full bg-blue-100 p-3 dark:bg-blue-900">
                  <Users className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-muted-foreground">{t('Branches')}</p>
                  <p className="mt-2 text-2xl font-bold">{stats.totalDepartments} {t('Services')}</p>
                  {/* <p className="text-xs text-muted-foreground mt-1">{stats.totalBranches}</p> */}
                </div>
                <div className="rounded-full bg-green-100 p-3 dark:bg-green-900">
                  <Building2 className="h-5 w-5 text-green-600 dark:text-green-400" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-muted-foreground">{t('Attendance Rate')}</p>
                  <p className="mt-2 text-2xl font-bold">{stats.attendanceRate}%</p>
                  {/* <p className="text-xs text-muted-foreground mt-1">{stats.presentToday} {t('present today')}</p> */}
                </div>
                <div className="rounded-full bg-purple-100 p-3 dark:bg-purple-900">
                  <PersonStanding className="h-5 w-5 text-purple-600 dark:text-purple-400" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-muted-foreground">{t('Pending Leaves')}</p>
                  <p className="mt-2 text-2xl font-bold">{stats.pendingLeaves}</p>
                  {/* <p className="text-xs text-muted-foreground mt-1">{stats.pendingLeavesToValidate}</p> */}
                </div>
                <div className="rounded-full bg-yellow-100 p-3 dark:bg-yellow-900">
                  <Briefcase className="h-5 w-5 text-yellow-600 dark:text-yellow-400" />
                </div>
              </div>
            </CardContent>
          </Card>

          {/* <Card>
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-muted-foreground">{t('Leaves Used (Year)')}</p>
                  <p className="mt-2 text-2xl font-bold">{stats.leavesUsedThisYear} {t('Days')}</p>
                </div>
                <div className="rounded-full bg-pink-100 p-3 dark:bg-pink-900">
                  <BarChart3 className="h-5 w-5 text-pink-600 dark:text-pink-400" />
                </div>
              </div>
            </CardContent>
          </Card> */}

          <Card>
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-muted-foreground">{t('Active Jobs')}</p>
                  <p className="mt-2 text-2xl font-bold">{stats.activeJobPostings}</p>
                  <p className="text-xs text-green-600 mt-1">+{stats.jobPostsThisMonth} {t('this month')}</p>
                </div>
                <div className="rounded-full bg-orange-100 p-3 dark:bg-orange-900">
                  <Briefcase className="h-5 w-5 text-orange-600 dark:text-orange-400" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-muted-foreground">{t('Total Candidates')}</p>
                  <p className="mt-2 text-2xl font-bold">{stats.totalCandidates}</p>
                  <p className="text-xs text-green-600 mt-1">+{stats.candidatesThisMonth} {t('this month')}</p>
                </div>
                <div className="rounded-full bg-indigo-100 p-3 dark:bg-indigo-900">
                  <UserPlus className="h-5 w-5 text-indigo-600 dark:text-indigo-400" />
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Charts Section */}
        <div className="grid gap-6 lg:grid-cols-2">
          {/* Department Distribution Chart */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-lg font-semibold">
                <BarChart3 className="h-5 w-5" />
                {t('Department Distribution')}
              </CardTitle>
            </CardHeader>
            <CardContent>
              {charts.departmentStats.length > 0 ? (
                <ResponsiveContainer width="100%" height={200}>
                  <PieChart>
                    <Pie
                      data={charts.departmentStats}
                      cx="50%"
                      cy="50%"
                      innerRadius={40}
                      outerRadius={80}
                      dataKey="value"
                    >
                      {charts.departmentStats.map((entry, index) => (
                        <Cell key={`cell-${index}`} fill={entry.color} />
                      ))}
                    </Pie>
                    <Tooltip />
                  </PieChart>
                </ResponsiveContainer>
              ) : (
                <div className="text-center py-8 text-muted-foreground">
                  {t('No department data available')}
                </div>
              )}
            </CardContent>
          </Card>

          {/* Candidate Status Chart */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-lg font-semibold">
                <UserPlus className="h-5 w-5" />
                {t('Candidate Status')}
              </CardTitle>
            </CardHeader>
            <CardContent>
              {charts.candidateStatusStats.length > 0 ? (
                <ResponsiveContainer width="100%" height={200}>
                  <PieChart>
                    <Pie
                      data={charts.candidateStatusStats}
                      cx="50%"
                      cy="50%"
                      outerRadius={80}
                      dataKey="value"
                    >
                      {charts.candidateStatusStats.map((entry, index) => (
                        <Cell key={`cell-${index}`} fill={entry.color} />
                      ))}
                    </Pie>
                    <Tooltip />
                    <Legend />
                  </PieChart>
                </ResponsiveContainer>
              ) : (
                <div className="text-center py-8 text-muted-foreground">
                  {t('No candidate data available')}
                </div>
              )}
            </CardContent>
          </Card>

          {/* Hiring & Departures Trend Chart */}
          <Card className="lg:col-span-2">
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-lg font-semibold">
                <TrendingUp className="h-5 w-5" />
                {t('Entrées & Sorties')}
              </CardTitle>
            </CardHeader>
            <CardContent>
              {charts.hiringTrend.length > 0 ? (
                <ResponsiveContainer width="100%" height={240}>
                  <LineChart data={charts.hiringTrend}>
                    <CartesianGrid strokeDasharray="3 3" />
                    <XAxis dataKey="month" />
                    <YAxis />
                    <Tooltip />
                    <Legend />
                    <Line type="monotone" dataKey="hires" stroke="#3b82f6" name={t('Hires')} strokeWidth={2} />
                    <Line type="monotone" dataKey="departures" stroke="#ef4444" name={t('Departures')} strokeWidth={2} />
                  </LineChart>
                </ResponsiveContainer>
              ) : (
                <div className="text-center py-8 text-muted-foreground">
                  {t('No data available')}
                </div>
              )}
            </CardContent>
          </Card>

          {/* Leave per Department Chart */}
          {/* <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-lg font-semibold">
                <BarChart3 className="h-5 w-5" />
                {t('Congés par Département')}
              </CardTitle>
            </CardHeader>
            <CardContent>
              {charts.leavePerDepartment.length > 0 ? (
                <ResponsiveContainer width="100%" height={240}>
                  <BarChart data={charts.leavePerDepartment} layout="vertical">
                    <CartesianGrid strokeDasharray="3 3" />
                    <XAxis type="number" />
                    <YAxis dataKey="name" type="category" width={100} />
                    <Tooltip />
                    <Bar dataKey="value" fill="#10b981" name={t('Leaves')} />
                  </BarChart>
                </ResponsiveContainer>
              ) : (
                <div className="text-center py-8 text-muted-foreground">
                  {t('No data available')}
                </div>
              )}
            </CardContent>
          </Card> */}
        </div>

        {/* Detailed Statistics Charts */}
        <div className="grid gap-6 lg:grid-cols-3">
          {/* Age Distribution */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-lg font-semibold">
                <BarChart3 className="h-5 w-5" />
                {t('Age Distribution')}
              </CardTitle>
            </CardHeader>
            <CardContent>
              {charts.ageDistribution?.length > 0 ? (
                <ResponsiveContainer width="100%" height={240}>
                  <BarChart data={charts.ageDistribution}>
                    <CartesianGrid strokeDasharray="3 3" />
                    <XAxis dataKey="age_group" />
                    <YAxis />
                    <Tooltip />
                    <Bar dataKey="total" fill="#3b82f6" name={t('Employees')} />
                  </BarChart>
                </ResponsiveContainer>
              ) : (
                <div className="text-center py-8 text-muted-foreground">{t('No data')}</div>
              )}
            </CardContent>
          </Card>

          {/* Marital Status */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-lg font-semibold">
                <PieChartIcon className="h-5 w-5" />
                {t('Marital Status')}
              </CardTitle>
            </CardHeader>
            <CardContent>
              {charts.maritalStatusStats?.length > 0 ? (
                <ResponsiveContainer width="100%" height={240}>
                  <PieChart>
                    <Pie
                      data={charts.maritalStatusStats}
                      cx="50%"
                      cy="50%"
                      outerRadius={80}
                      dataKey="value"
                      label
                    >
                      {charts.maritalStatusStats.map((entry, index) => (
                        <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                      ))}
                    </Pie>
                    <Tooltip />
                    <Legend />
                  </PieChart>
                </ResponsiveContainer>
              ) : (
                <div className="text-center py-8 text-muted-foreground">{t('No data')}</div>
              )}
            </CardContent>
          </Card>

          {/* Gender by Personnel Type */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-lg font-semibold">
                <BarChart3 className="h-5 w-5" />
                {t('Gender by Personnel Type')}
              </CardTitle>
            </CardHeader>
            <CardContent>
              {charts.genderByPersonnelType?.length > 0 ? (
                <ResponsiveContainer width="100%" height={240}>
                  <BarChart data={charts.genderByPersonnelType}>
                    <CartesianGrid strokeDasharray="3 3" />
                    <XAxis dataKey="type" />
                    <YAxis />
                    <Tooltip />
                    <Legend />
                    <Bar dataKey="male" stackId="a" fill="#3b82f6" name={t('Male')} />
                    <Bar dataKey="female" stackId="a" fill="#ec4899" name={t('Female')} />
                  </BarChart>
                </ResponsiveContainer>
              ) : (
                <div className="text-center py-8 text-muted-foreground">{t('No data')}</div>
              )}
            </CardContent>
          </Card>
        </div>

        {/* Detailed Tables */}
        <div className="grid gap-6 lg:grid-cols-2">
          {/* Staff Distribution by Year and Contract */}
          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-lg font-semibold">{t('Staff by Year & Contract')}</CardTitle>
              <div className="flex items-center gap-2">
                <input
                  type="text"
                  placeholder={t('Search year...')}
                  className="text-xs border rounded px-2 py-1 outline-none focus:ring-1 ring-blue-500"
                  value={searchStaff}
                  onChange={(e) => setSearchStaff(e.target.value)}
                />
              </div>
            </CardHeader>
            <CardContent>
              <div className="max-h-[300px] overflow-auto">
                <table className="w-full text-sm">
                  <thead className="sticky top-0 bg-white">
                    <tr className="text-left border-b font-medium text-muted-foreground">
                      <th className="pb-2">{t('Year')}</th>
                      <th className="pb-2">CDD</th>
                      <th className="pb-2">CDI</th>
                      <th className="pb-2">Autre</th>
                    </tr>
                  </thead>
                  <tbody>
                    {filteredStaff.map((row, idx) => (
                      <tr key={idx} className="border-b last:border-0">
                        <td className="py-2 font-medium">{row.year}</td>
                        <td className="py-2">{row.CDD || 0}</td>
                        <td className="py-2">{row.CDI || 0}</td>
                        <td className="py-2">{row.Autre || 0}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </CardContent>
          </Card>

          {/* Medical Corps Composition */}
          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-lg font-semibold">{t('Medical Corps Composition')}</CardTitle>
              <div className="flex items-center gap-2">
                <input
                  type="text"
                  placeholder={t('Search specialty...')}
                  className="text-xs border rounded px-2 py-1 outline-none focus:ring-1 ring-blue-500"
                  value={searchMedical}
                  onChange={(e) => setSearchMedical(e.target.value)}
                />
              </div>
            </CardHeader>
            <CardContent>
              <div className="max-h-[300px] overflow-auto">
                <table className="w-full text-sm">
                  <thead className="sticky top-0 bg-white">
                    <tr className="text-left border-b font-medium text-muted-foreground">
                      <th className="pb-2">{t('Specialty')}</th>
                      <th className="pb-2">{t('Male')}</th>
                      <th className="pb-2">{t('Female')}</th>
                      <th className="pb-2">{t('Total')}</th>
                    </tr>
                  </thead>
                  <tbody>
                    {filteredMedical.map((row, idx) => (
                      <tr key={idx} className="border-b last:border-0">
                        <td className="py-2 font-medium">{row.specialty}</td>
                        <td className="py-2 text-blue-600">{row.male}</td>
                        <td className="py-2 text-pink-600">{row.female}</td>
                        <td className="py-2 font-bold">{row.total}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </CardContent>
          </Card>

          {/* T/S Staff Composition */}
          <Card className="lg:col-span-2">
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-lg font-semibold">{t('T/S Staff Composition')}</CardTitle>
              <div className="flex items-center gap-2">
                <input
                  type="text"
                  placeholder={t('Search specialty...')}
                  className="text-xs border rounded px-2 py-1 outline-none focus:ring-1 ring-blue-500"
                  value={searchTechnique}
                  onChange={(e) => setSearchTechnique(e.target.value)}
                />
              </div>
            </CardHeader>
            <CardContent>
              <div className="max-h-[300px] overflow-auto">
                <table className="w-full text-sm">
                  <thead className="sticky top-0 bg-white">
                    <tr className="text-left border-b font-medium text-muted-foreground">
                      <th className="pb-2">{t('Specialty')}</th>
                      <th className="pb-2">{t('Male')}</th>
                      <th className="pb-2">{t('Female')}</th>
                      <th className="pb-2">{t('Total')}</th>
                    </tr>
                  </thead>
                  <tbody>
                    {filteredTechnique.map((row, idx) => (
                      <tr key={idx} className="border-b last:border-0">
                        <td className="py-2 font-medium">{row.specialty}</td>
                        <td className="py-2">{row.male}</td>
                        <td className="py-2">{row.female}</td>
                        <td className="py-2 font-bold">{row.male + row.female}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Employee Growth Chart - Full Width */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-lg font-semibold">
              <TrendingUp className="h-5 w-5" />
              {t('Employee Growth')} ({new Date().getFullYear()})
            </CardTitle>
          </CardHeader>
          <CardContent>
            {charts.employeeGrowthChart.length > 0 ? (
              <ResponsiveContainer width="100%" height={400}>
                <AreaChart data={charts.employeeGrowthChart}>
                  <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" />
                  <XAxis dataKey="month" stroke="#6b7280" />
                  <YAxis stroke="#6b7280" />
                  <Tooltip
                    contentStyle={{
                      backgroundColor: '#ffffff',
                      border: '1px solid #e5e7eb',
                      borderRadius: '8px',
                      boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1)'
                    }}
                  />
                  <Area
                    type="monotone"
                    dataKey="employees"
                    stroke="#3b82f6"
                    strokeWidth={3}
                    fillOpacity={0.2}
                    fill="#3b82f6"
                    dot={{ fill: '#3b82f6', strokeWidth: 2, r: 5 }}
                  />
                </AreaChart>
              </ResponsiveContainer>
            ) : (
              <div className="text-center py-8 text-muted-foreground">
                {t('No employee growth data available')}
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </PageTemplate>
  );
}