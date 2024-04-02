import { faker } from '@faker-js/faker';

import { SocialMedia } from '../../types/social-media';

export const testSocialItem: SocialMedia = {
  id: faker.string.uuid(),
  alt: faker.lorem.word(),
  linkHref: faker.internet.url(),
  ariaLabel: faker.lorem.word(),
  icon: faker.image.avatar(),
  type: 'drawer',
};
