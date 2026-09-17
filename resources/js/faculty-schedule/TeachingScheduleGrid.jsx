import DayCard from './DayCard';

export default function TeachingScheduleGrid({ teacherName, schoolYear, days }) {
    return (
        <div className="space-y-6">
            <div>
                <h1 className="font-heading text-2xl font-semibold text-brandNavy dark:text-white">Weekly teaching schedule</h1>
                <p className="text-sm text-brandNavy/60 dark:text-slate-400 mt-1">
                    {teacherName} — A.Y. {schoolYear}
                </p>
            </div>

            {days.length === 0 ? (
                <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm p-10 text-center">
                    <i className="fa-solid fa-chalkboard-user text-3xl text-brandNavy/20 dark:text-slate-600 mb-3" />
                    <p className="text-sm text-brandNavy/60 dark:text-slate-400">No teaching load assigned yet for this school year.</p>
                </div>
            ) : (
                days.map((day) => <DayCard key={day.label} label={day.label} sections={day.sections} />)
            )}
        </div>
    );
}
