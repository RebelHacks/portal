"use client";
import { useState } from "react";
import { useRouter } from "next/navigation";
import styles from "../login.module.css";
import api from "@/lib/api";

export default function RegisterPage() {
  const router = useRouter();
  const [isJudge, setIsJudge] = useState<boolean | null>(false);
  const [showTooltip, setShowTooltip] = useState(false);

  const [major, setMajor] = useState("");
  const [track, setTrack] = useState("Software");
  const [transcript, setTranscript] = useState<File | null>(null);
  const [loading, setLoading] = useState(false);

  const handleRegister = async (e: React.FormEvent) => {
    e.preventDefault(); // Prevents page reload
    setLoading(true);
    try {
      if (!isJudge) {
        // 1. Update Profile
        await api.patch('/users/profile', { major, track });
          const fileData = new FormData();
          fileData.append("file", transcript);
          fileData.append("type", "transcript");
          await api.post('/users/upload-file', fileData, {
            headers: { 'Content-Type': 'multipart/form-data' },
            withCredentials: true,
          });
      }

      router.push("/dashboard");
    } catch (error: any) {
      console.error("Registration step failed:", error);
      alert(error.response?.data?.message || "An error occurred.");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className={`${styles.container} min-h-screen flex items-center justify-center`}>
      <div className={`${styles.portalCard}`}>
        <div className="text-center mb-6">
          <h1 className="text-2xl font-bold">Registration</h1>
        </div>

        {/* Form Start */}
        <form onSubmit={handleRegister} className="w-full flex flex-col gap-4">
          <div className="flex flex-col gap-3">
            <span className="text-md font-medium">Are you a judge?</span>
            <div className="flex flex-row gap-4">
              {['Yes', 'No'].map((option) => (
                <label key={option} className="flex items-center gap-2 cursor-pointer group">
                  <input
                    type="radio"
                    name="isJudge"
                    value={option}
                    checked={isJudge === (option === 'Yes')}
                    onChange={() => setIsJudge(option === 'Yes')}
                    className="w-4 h-4 accent-cyan-400"
                  />
                  <span className="group-hover:text-cyan-400 transition-colors">{option}</span>
                </label>
              ))}
            </div>

            {isJudge ? (
              <div className="bg-slate-800/50 p-4 rounded-lg border border-slate-600 mb-4">
                <h3 className="text-lg font-semibold text-cyan-400 mb-3">Judge Registration Process</h3>
                <ul className="text-base text-gray-200 space-y-3">
                  <li className="flex items-start gap-3">
                    <span className="text-cyan-400 text-lg mt-0.5">•</span>
                    <span>Click the register button below to submit your judge application.</span>
                  </li>
                </ul>
              </div>
            ) : (
              <div className="flex flex-col gap-4">
                <div className="flex flex-col text-md">
                  <label className="mb-2">What is your major?</label>
                  <input
                    type="text"
                    required={!isJudge}
                    value={major}
                    onChange={(e) => setMajor(e.target.value)}
                    disabled={loading}
                    className={`${styles.input} w-full px-3 py-2 rounded-md bg-[#0a2a4a] border border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-400 disabled:opacity-50`}
                    placeholder="Major"
                  />
                </div>

                <div className="flex flex-col text-md">
                  <label className="mb-2">Select your track</label>
                  <select
                    required={!isJudge}
                    value={track}
                    onChange={(e) => setTrack(e.target.value)}
                    disabled={loading}
                    className={`${styles.input} w-full px-3 py-2 rounded-md bg-[#0a2a4a] border border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-400 disabled:opacity-50`}
                  >
                    <option value="Choose">Choose</option>
                    <option value="Software">Software</option>
                    <option value="Hardware">Hardware</option>
                  </select>
                </div>

                <div className="flex flex-col text-md">
                  <div className="group relative w-full">
                    <div className="flex items-center gap-2 mb-2">
                      <label>Upload your unofficial transcript</label>
                      <span
                        className="cursor-help text-cyan-400 text-xs border border-cyan-400 rounded-full px-1.5 flex-shrink-0"
                        onClick={() => setShowTooltip(!showTooltip)}
                      >?</span>
                    </div>
                    {showTooltip && (
                      <div className="w-full p-2 bg-slate-800 text-xs text-white rounded transition-opacity z-50 shadow-lg border border-slate-700 mb-2">
                        Used to verify enrollment.
                      </div>
                    )}
                  </div>

                  <input
                    type="file"
                    accept=".pdf"
                    required={!isJudge}
                    disabled={loading}
                    onChange={(e) => setTranscript(e.target.files?.[0] || null)}
                    className={`${styles.input} pt-1 w-full text-sm text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-cyan-500 file:text-white hover:file:bg-cyan-600 cursor-pointer disabled:opacity-50`}
                  />
                </div>
              </div>
            )}
          </div>

          <button
            type="submit"
            disabled={loading || (!isJudge && (!major || track === "Choose"))}
            className={`${styles.button} w-full mt-2 px-3 py-2 rounded-md bg-orange-500 hover:bg-orange-600 font-bold text-white disabled:opacity-50 disabled:cursor-not-allowed transition-opacity`}
          >
            {loading ? "Registering..." : "Register"}
          </button>
        </form>
        {/* Form End */}
      </div>
    </div>
  );
}