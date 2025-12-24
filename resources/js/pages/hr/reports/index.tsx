import React, { useState, useEffect } from 'react';
import { PageTemplate } from '@/components/page-template';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { FileDown, Users, Calendar, Clock, BarChart3, PieChart as PieChartIcon, Download } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { PieChart, Pie, Cell, ResponsiveContainer, Tooltip, BarChart, Bar, XAxis, YAxis, CartesianGrid, Legend, LineChart, Line } from 'recharts';
import axios from 'axios';
import { format } from 'date-fns';
import { Badge } from '@/components/ui/badge';

const COLORS = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'];

export default function Reports({ departments = [] }: { departments?: any[] }) {
    const { t } = useTranslation();
    const [activeTab, setActiveTab] = useState('headcount');
    const [data, setData] = useState<any>(null);
    const [loading, setLoading] = useState(false);
    const [filters, setFilters] = useState({
        department_id: 'all',
        year: new Date().getFullYear().toString(),
    });

    const fetchData = async () => {
        setLoading(true);
        try {
            const endpoint = `/hr/reports/${activeTab}`;
            const response = await axios.get(endpoint, { params: filters });
            setData(response.data);
        } catch (error) {
            console.error("Error fetching report data", error);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchData();
    }, [activeTab, filters]);

    const handleExport = (format: string) => {
        const queryParams = new URLSearchParams({
            ...filters,
            type: activeTab,
            format: format
        }).toString();
        window.location.href = `/hr/reports/export?${queryParams}`;
    };

    const renderHeadcountCharts = () => {
        if (!data?.charts?.byDepartment || !data?.charts?.byContractType) return null;
        const deptData = Object.entries(data.charts.byDepartment).map(([name, value]) => ({ name, value }));
        const contractData = Object.entries(data.charts.byContractType).map(([name, value]) => ({ name, value }));

        return (
            <div className="grid gap-6 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm font-medium">{t('Effectif par Département')}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ResponsiveContainer width="100%" height={300}>
                            <PieChart>
                                <Pie data={deptData} dataKey="value" nameKey="name" cx="50%" cy="50%" outerRadius={80} label>
                                    {deptData.map((_, index) => <Cell key={index} fill={COLORS[index % COLORS.length]} />)}
                                </Pie>
                                <Tooltip />
                                <Legend />
                            </PieChart>
                        </ResponsiveContainer>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm font-medium">{t('Effectif par Type de Contrat')}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ResponsiveContainer width="100%" height={300}>
                            <BarChart data={contractData}>
                                <CartesianGrid strokeDasharray="3 3" />
                                <XAxis dataKey="name" />
                                <YAxis />
                                <Tooltip />
                                <Bar dataKey="value" fill="#3b82f6" />
                            </BarChart>
                        </ResponsiveContainer>
                    </CardContent>
                </Card>
            </div>
        );
    };

    const renderLeaveCharts = () => {
        if (!data?.charts?.byMonth || !data?.charts?.byType) return null;
        const monthData = Object.entries(data.charts.byMonth).map(([month, value]) => ({ month, value }));
        const typeData = Object.entries(data.charts.byType).map(([name, value]) => ({ name, value }));

        return (
            <div className="grid gap-6 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm font-medium">{t('Jours de Congés par Mois')}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ResponsiveContainer width="100%" height={300}>
                            <LineChart data={monthData}>
                                <CartesianGrid strokeDasharray="3 3" />
                                <XAxis dataKey="month" />
                                <YAxis />
                                <Tooltip />
                                <Line type="monotone" dataKey="value" stroke="#10b981" strokeWidth={2} />
                            </LineChart>
                        </ResponsiveContainer>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm font-medium">{t('Répartition par Type de Congé')}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ResponsiveContainer width="100%" height={300}>
                            <PieChart>
                                <Pie data={typeData} dataKey="value" nameKey="name" cx="50%" cy="50%" outerRadius={80} innerRadius={40}>
                                    {typeData.map((_, index) => <Cell key={index} fill={COLORS[index % COLORS.length]} />)}
                                </Pie>
                                <Tooltip />
                                <Legend />
                            </PieChart>
                        </ResponsiveContainer>
                    </CardContent>
                </Card>
            </div>
        );
    };

    const renderAbsenteeismCharts = () => {
        if (!data?.charts?.byDate || !data?.charts?.byDepartment) return null;
        const dateData = Object.entries(data.charts.byDate).map(([date, value]) => ({ date, value }));
        const deptData = Object.entries(data.charts.byDepartment).map(([name, value]) => ({ name, value }));

        return (
            <div className="grid gap-6 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm font-medium">{t('Absences par Date')}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ResponsiveContainer width="100%" height={300}>
                            <BarChart data={dateData}>
                                <CartesianGrid strokeDasharray="3 3" />
                                <XAxis dataKey="date" />
                                <YAxis />
                                <Tooltip />
                                <Bar dataKey="value" fill="#ef4444" />
                            </BarChart>
                        </ResponsiveContainer>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm font-medium">{t('Absences par Département')}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ResponsiveContainer width="100%" height={300}>
                            <PieChart>
                                <Pie data={deptData} dataKey="value" nameKey="name" cx="50%" cy="50%" outerRadius={80}>
                                    {deptData.map((_, index) => <Cell key={index} fill={COLORS[index % COLORS.length]} />)}
                                </Pie>
                                <Tooltip />
                                <Legend />
                            </PieChart>
                        </ResponsiveContainer>
                    </CardContent>
                </Card>
            </div>
        );
    };

    return (
        <PageTemplate
            title={t('Rapports RH')}
            url="/hr/reports"
            description={t('Générez et analysez vos rapports RH détaillés')}
        >
            <div className="space-y-6">
                <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <Tabs value={activeTab} onValueChange={setActiveTab} className="w-full md:w-auto">
                        <TabsList>
                            <TabsTrigger value="headcount" className="flex items-center gap-2">
                                <Users className="h-4 w-4" /> {t('Effectifs')}
                            </TabsTrigger>
                            <TabsTrigger value="leave" className="flex items-center gap-2">
                                <Calendar className="h-4 w-4" /> {t('Congés')}
                            </TabsTrigger>
                            <TabsTrigger value="absenteeism" className="flex items-center gap-2">
                                <Clock className="h-4 w-4" /> {t('Absentéisme')}
                            </TabsTrigger>
                        </TabsList>
                    </Tabs>

                    <div className="flex items-center gap-2">
                        <Button variant="outline" size="sm" onClick={() => handleExport('xlsx')} className="flex items-center gap-2 text-green-600 border-green-200 hover:bg-green-50">
                            <Download className="h-4 w-4" /> Excel
                        </Button>
                        <Button variant="outline" size="sm" onClick={() => handleExport('pdf')} className="flex items-center gap-2 text-red-600 border-red-200 hover:bg-red-50">
                            <FileDown className="h-4 w-4" /> PDF
                        </Button>
                    </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-4 gap-4 bg-muted/50 p-4 rounded-lg">
                    <div className="space-y-1">
                        <span className="text-xs font-medium text-muted-foreground">{t('Département')}</span>
                        <Select
                            value={filters.department_id}
                            onValueChange={(val) => setFilters({ ...filters, department_id: val })}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder={t('Tous les départements')} />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">{t('Tous les départements')}</SelectItem>
                                {departments.map((dept: any) => (
                                    <SelectItem key={dept.id} value={dept.id.toString()}>{dept.name}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    {activeTab === 'leave' && (
                        <div className="space-y-1">
                            <span className="text-xs font-medium text-muted-foreground">{t('Année')}</span>
                            <Select
                                value={filters.year}
                                onValueChange={(val) => setFilters({ ...filters, year: val })}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder={t('Choisir l\'année')} />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="2023">2023</SelectItem>
                                    <SelectItem value="2024">2024</SelectItem>
                                    <SelectItem value="2025">2025</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    )}
                </div>

                {loading ? (
                    <div className="flex items-center justify-center p-20">
                        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    </div>
                ) : (
                    <div className="space-y-6">
                        {activeTab === 'headcount' && renderHeadcountCharts()}
                        {activeTab === 'leave' && renderLeaveCharts()}
                        {activeTab === 'absenteeism' && renderAbsenteeismCharts()}

                        <Card>
                            <CardHeader>
                                <CardTitle className="text-lg font-semibold flex items-center gap-2">
                                    <BarChart3 className="h-5 w-5 text-blue-500" />
                                    {t('Détails du Rapport')}
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="overflow-x-auto">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="border-b bg-muted/30">
                                                <th className="p-3 text-left font-medium">{t('Nom')}</th>
                                                <th className="p-3 text-left font-medium">{t('Détails')}</th>
                                                <th className="p-3 text-left font-medium text-center">{t('Statut')}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {data?.data?.length > 0 ? (
                                                data.data.map((item: any, idx: number) => (
                                                    <tr key={idx} className="border-b hover:bg-muted/10 transition-colors">
                                                        <td className="p-3">
                                                            <div className="font-medium">{item.user?.name || item.employee?.name || t('N/A')}</div>
                                                            <div className="text-xs text-muted-foreground">{item.employee_id || item.employee?.employee?.employee_id}</div>
                                                        </td>
                                                        <td className="p-3 text-xs">
                                                            {activeTab === 'headcount' && (
                                                                <>
                                                                    <div>{item.department?.name}</div>
                                                                    <div className="text-muted-foreground">{item.designation?.name}</div>
                                                                </>
                                                            )}
                                                            {activeTab === 'leave' && (
                                                                <>
                                                                    <div>{item.leave_type?.title || item.leave_type?.name || t('N/A')}</div>
                                                                    <div className="text-muted-foreground">{item.total_days} {t('jours')} ({item.start_date})</div>
                                                                </>
                                                            )}
                                                            {activeTab === 'absenteeism' && (
                                                                <>
                                                                    <div>{t('Absence le')} {item.date}</div>
                                                                    <div className="text-muted-foreground">{item.notes || t('Aucune note')}</div>
                                                                </>
                                                            )}
                                                        </td>
                                                        <td className="p-3 text-center">
                                                            <Badge variant="outline" className="text-[10px]">
                                                                {item.status || (item.user?.is_active ? t('Actif') : t('Inactif'))}
                                                            </Badge>
                                                        </td>
                                                    </tr>
                                                ))
                                            ) : (
                                                <tr>
                                                    <td colSpan={3} className="p-10 text-center text-muted-foreground">
                                                        {t('Aucune donnée trouvée')}
                                                    </td>
                                                </tr>
                                            )}
                                        </tbody>
                                    </table>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                )}
            </div>
        </PageTemplate>
    );
}
