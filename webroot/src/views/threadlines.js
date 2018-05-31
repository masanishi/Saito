import $ from 'jquery';
import _ from 'underscore';
import Marionette from 'backbone.marionette';
import App from 'models/app';
import ThreadLineModel from 'models/threadline';

import PostingLayout from 'views/postingLayout';
import threadlineSpinnerTpl from 'templates/threadline-spinner.html';
import PostingModel from 'models/posting';
import 'lib/saito/jquery.scrollIntoView';

export default Marionette.View.extend({

  className: 'threadLeaf',

  tagName: 'li',

  spinnerTpl: threadlineSpinnerTpl,

  /**
   * Posting collection
   */
  postings: null,

  ui: {
    btnShowThread: '.btn_show_thread',
    btnClose: '.js-btn-strip',
    linkShowThread: '.link_show_thread',
  },

  events: {
    'click @ui.btnShowThread': 'toggleInlineOpen',
    'click @ui.linkShowThread': 'toggleInlineOpenFromLink',
    'click @ui.btnClose': 'toggleInlineOpen',
  },

  initialize: function (options) {
    this.postings = options.postings;

    this.model = new ThreadLineModel({
      id: options.leafData.id,
      isNewToUser: options.leafData.isNewToUser
    });
    if (options.el === undefined) {
      this.model.fetch();
    } else {
      this.model.set({ html: this.el });
    }
    this.collection.add(this.model, { silent: true });
    this.attributes = { 'data-id': options.id };

    this.listenTo(this.model, 'change:isInlineOpened', this._toggleInlineOpened);
    this.listenTo(this.model, 'change:html', this.render);
  },

  toggleInlineOpenFromLink: function (event) {
    if (this.model.get('isAlwaysShownInline')) {
      this.toggleInlineOpen(event);
    }
  },

  /**
   * shows and hides the element that contains an inline posting
   */
  toggleInlineOpen: function (event) {
    event.preventDefault();
    this.model.toggle('isInlineOpened');
  },

  _toggleInlineOpened: function (model, isInlineOpened) {
    if (!isInlineOpened) {
      this._closeInlineView();
      return;
    }
    if (!this.model.get('isContentLoaded')) {
      this.$('.threadLine').after(this.spinnerTpl);
      this._insertContent();
      this.model.set('isContentLoaded', true);
    }
    this._showInlineView();
  },

  _insertContent: function () {
    var id = this.model.get('id'),
      postingLayout;

    this.postingModel = new PostingModel({ id: id });
    this.postings.add(this.postingModel);

    postingLayout = new PostingLayout({
      el: this.$('.threadInline-slider'),
      inline: true,
      model: this.postingModel,
      collection: this.postings,
      parentThreadline: this.model
    });
  },

  _showInlineView: function () {
    var postShow = _.bind(function () {
      var shouldScrollOnInlineOpen = this.model.get('shouldScrollOnInlineOpen');
      if (shouldScrollOnInlineOpen) {
        if (this.$el.scrollIntoView('isInView') === false) {
          this.$el.scrollIntoView('bottom');
        }
      } else {
        this.model.set('shouldScrollOnInlineOpen', true);
      }
    }, this);

    this.$('.threadLine').fadeOut(100, _.bind(
      function () {
        // performance: show() instead slide()
        // this.$('.js-thread_inline.' + id).slideDown(0,
        this.$('.js-thread_inline').show(0, postShow);
      }, this));
  },

  _closeInlineView: function () {
    App.eventBus.trigger('change:DOM');
    this.$('.js-thread_inline').hide(0,
      _.bind(
        function () {
          this.$el.find('.threadLine').slideDown();
          this._scrollLineIntoView();
        },
        this
      )
    );
  },

  /**
   * if the line is not in the browser windows at the moment
   * scroll to that line and highlight it
   */
  _scrollLineIntoView: function () {
    var thread_line = this.$('.threadLine');
    if (!thread_line.scrollIntoView('isInView')) {
      thread_line.scrollIntoView('top')
        .effect(
          "highlight",
          {
            times: 1
          },
          3000);
    }
  },

  render: function () {
    var $oldEl,
      newHtml,
      $newEl;

    newHtml = this.model.get('html');
    if (newHtml.length > 0) {
      $oldEl = this.$el;
      $newEl = $(this.model.get('html'));
      this.setElement($newEl);
      $oldEl.replaceWith($newEl);
    }
    return this;
  }
});