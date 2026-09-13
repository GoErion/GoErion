<?php

declare(strict_types=1);

namespace App\Enums;

enum CompanyAccessEventType: string
{
    case InvitationCreated = 'invitation.created';
    case InvitationResent = 'invitation.resent';
    case InvitationCancelled = 'invitation.cancelled';
    case InvitationAccepted = 'invitation.accepted';
    case MemberJoined = 'member.joined';
    case RoleCreated = 'role.created';
    case RoleUpdated = 'role.updated';
    case RoleDeleted = 'role.deleted';
    case MemberRolesUpdated = 'member.roles_updated';
    case OwnerPromoted = 'owner.promoted';
    case OwnerDemoted = 'owner.demoted';
    case MemberRemoved = 'member.removed';
    case MemberLeft = 'member.left';
}
