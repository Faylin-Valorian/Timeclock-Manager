import UsersFeature from './users.feature.js';
import HolidaysFeature from './holidays.feature.js';
import AccessFeature from './access.feature.js';

export const ADMIN_FEATURES = [
    UsersFeature,
    HolidaysFeature,
    AccessFeature
].filter((feature) => feature && feature.id && feature.label);

