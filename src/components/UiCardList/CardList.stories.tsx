import type { Meta, StoryObj } from '@storybook/react';
import { initReactI18next } from 'react-i18next';

import i18n from 'i18n';

import resources from '../../../pages/i18n/localization.json';

import { LARGE_CARDLIST_ARRAY, SMALL_CARDLIST_ARRAY } from './constants';

import UiCardList from './index';

i18n.use(initReactI18next).init({
  resources,
  lng: 'en',
});

const meta: Meta<typeof UiCardList> = {
  title: 'UiComponents/UiCardList',
  component: UiCardList,
  tags: ['autodocs'],
  argTypes: {
    cardList: {
      control: 'object',
      description: 'List of card items',
    },
  },
};

export default meta;

type Story = StoryObj<typeof UiCardList>;

export const CardListLarge: Story = {
  args: {
    cardList: LARGE_CARDLIST_ARRAY,
  },
};

export const CardListSmall: Story = {
  args: {
    cardList: SMALL_CARDLIST_ARRAY,
  },
};
