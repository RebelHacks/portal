'use client';

import { TeamViewDash } from "./TeamViewDash";
import { TeamCreationDash } from "./TeamCreationDash";
import { TeamInvitationsDash } from "./TeamInvitationDash";
import { TeamProjectDash } from "./TeamProjectDash";
import { TeamProvider, useTeamContext } from "./TeamContext";

export function Team() {
  return (
    <TeamProvider>
      <TeamContent />
    </TeamProvider>
  );
}

function TeamContent() {
  const { loading, teamId } = useTeamContext();

  if (loading) {
    return <div className="text-gray-400 text-center py-10">Loading...</div>;
  }

  return (
    <>
      {teamId ? (
        <>
          <TeamViewDash />
          <TeamProjectDash />
        </>
      ) : (
        <>
          <TeamCreationDash />
          <TeamInvitationsDash />
        </>
      )}
    </>
  );
}