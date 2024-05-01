import { faker } from '@faker-js/faker';

import { CardItem } from '@/components/UiCardList/types';

import { SocialMedia } from '../../features/landing/types/social-media';

export const testId: string = faker.string.uuid();
export const testTitle: string = faker.lorem.word(6);
export const testText: string = faker.lorem.word(6);
export const testImg: string = faker.image.avatar();
export const testInitials: string = faker.person.fullName();
export const testEmail: string = faker.internet.email();
export const testPassword: string = faker.internet.password();
export const testPlaceholder: string = faker.lorem.word(8);
export const testUrl: string = faker.internet.url();
export const mockEmail: string = 'info@vilnacrm.com';

export const typeOfCard: string = 'smallCard';

export const cardItem: CardItem = {
  id: testId,
  title: testTitle,
  text: testText,
  type: typeOfCard,
  alt: testText,
  imageSrc: testImg,
};
export const smallCard: CardItem = {
  id: testId,
  title: testTitle,
  text: testText,
  type: 'smallCard',
  alt: testText,
  imageSrc: testImg,
};
export const largeCard: CardItem = {
  id: testId,
  title: testTitle,
  text: testText,
  type: 'largeCard',
  alt: testText,
  imageSrc: testImg,
};

export const cardList: CardItem[] = [
  {
    id: testId,
    title: testTitle,
    text: testText,
    type: typeOfCard,
    alt: testText,
    imageSrc: testImg,
  },
];
export const smallCardList: CardItem[] = [
  {
    id: testId,
    title: testTitle,
    text: testText,
    type: 'smallCard',
    alt: testText,
    imageSrc: testImg,
  },
];
export const largeCardList: CardItem[] = [
  {
    id: testId,
    title: testTitle,
    text: testText,
    type: 'largeCard',
    alt: testText,
    imageSrc: testImg,
  },
];

export const mockedSocialLinks: SocialMedia[] = [
  {
    id: testId,
    icon: testImg,
    alt: testText,
    linkHref: 'https://www.instagram.com/',
    ariaLabel: testTitle,
  },
];
