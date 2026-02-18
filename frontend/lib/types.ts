// API Response Types
export interface ApiTestResponse {
  message: string;
  timestamp: string;
  data?: unknown;
}

export interface ApiHealthResponse {
  status: string;
  version: string;
  timestamp: string;
}

export interface LoginResponse {
  token: string;
  refresh_token?: string;
}

export interface LoginRequest {
  email: string;
  password: string;
}

export interface RegisterRequest {
  email: string;
  password: string;
  agreeTerms?: boolean;
}

export interface CreateTeamRequest {
  teamName: string;
  track: Track;
}

export interface ErrorResponse {
  message: string;
  error?: string;
}

export type ArrivalState = "Pending" | "Checked In";
export type Track = "Software" | "Hardware";

export interface User {
  id: number;
  name: string;
  email: string;
  team: string;
  track: Track;
  state: ArrivalState;
}

export type TeamStatus = "Verified" | "Unverified";

export interface TeamMember {
  id: number;
  name: string;
  email: string;
  track: Track | "";
  state: ArrivalState;
}

export interface TeamProject {
  name: string;
  details: string;
}

export interface Team {
  id: number;
  teamName: string;
  status: TeamStatus;
  track: Track;
  project: TeamProject;
  assignments: Record<string, number[]>;
  leaderId: number | null;
  users?: TeamMember[];
}

export interface Judge {
  id: number;
  name: string;
  email: string;
}

export interface Invitation {
  id: number;
  teamId: number;
  teamName: string;
  status: string;
  invitee?: {
    id: number;
    name: string;
    email: string;
  };
}

export type RoundOption = {
  id: string;
  name: string;
};
