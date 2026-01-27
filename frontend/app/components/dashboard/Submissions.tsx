import style from "../../dashboard/dashboard.module.css";

export function Submissions() {
  return (
    <div className={style.card}>
      <h2 className={style.primaryTitle}>Project Submission</h2>
      <p className="text-gray-400 mt-4">Submission stuff goes here...</p>
    </div>
  );
}