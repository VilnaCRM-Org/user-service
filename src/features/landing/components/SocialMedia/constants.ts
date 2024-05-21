import { faker } from '@faker-js/faker';

import { SocialMedia } from '../../types/social-media';

export const testSocialDrawerItem: SocialMedia = {
  id: faker.string.uuid(),
  alt: faker.lorem.word(),
  linkHref: faker.internet.url(),
  ariaLabel: faker.lorem.word(),
  icon: faker.image.avatar(),
  type: 'drawer',
};

export const testSocialNoDrawerItem: SocialMedia = {
  id: faker.string.uuid(),
  alt: faker.lorem.word(),
  linkHref: faker.internet.url(),
  ariaLabel: faker.lorem.word(),
  icon: faker.image.avatar(),
  type: 'no-drawer',
};
