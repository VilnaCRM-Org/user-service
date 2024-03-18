import type { Meta, StoryObj } from '@storybook/react';
import { initReactI18next } from 'react-i18next';

import i18n from 'i18n';

import resources from '../../../pages/i18n/localization.json';

import UiFooter from './UiFooter';

i18n.use(initReactI18next).init({
  resources,
  lng: 'en',
});

const meta: Meta<typeof UiFooter> = {
  title: 'UiComponents/UiFooter',
  component: UiFooter,
  tags: ['autodocs'],
};

export default meta;

type Story = StoryObj<typeof UiFooter>;

export const Footer: Story = {};
