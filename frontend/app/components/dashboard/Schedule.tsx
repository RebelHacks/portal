import style from "../../dashboard/dashboard.module.css";

export function Schedule() {
  return (
    <div className={style.card}>
      <h2 className={style.primaryTitle}>Event Schedule</h2>

      <h3 className="text-[var(--primary)] font-bold mt-6 mb-3 text-lg">Friday, February 20th</h3>
      <ul className="space-y-3 text-gray-300">
        <li>8:00 AM - 9:00 AM: Check In & Breakfast</li>
        <li>9:00 AM - 10:30 AM: Opening Ceremony</li>
        <li>10:30 AM - 11:00 AM: Team Lock-In & Hacking Begins</li>
        <li>2:00 PM - 3:00 PM: Lunch</li>
        <li>7:00 PM - 8:00 PM: Dinner</li>
        <li>9:30 PM: Hackers Exit UNLV</li>
      </ul>
    
      <h3 className="text-[var(--primary)] font-bold mt-8 mb-3 text-lg">Saturday, February 21st</h3>
      <ul className="space-y-3 text-gray-300">
        <li>8:00 AM - 9:00 AM: Breakfast</li>
        <li>11:00 AM: Project Submission</li>
        <li>12:00 PM - 1:00 PM: Lunch</li>
        <li>2:00 PM - 4:00 PM: Judging</li>
        <li>6:00 - 6:30 PM: Closing Ceremony</li>
      </ul>
    </div>
  );
}